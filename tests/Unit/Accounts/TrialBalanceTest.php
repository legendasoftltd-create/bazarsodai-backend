<?php

namespace Tests\Unit\Accounts;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Accounts\Services\AccountingService;
use Tests\TestCase;

/**
 * Tests for Phase 7: trial balance and general ledger.
 */
class TrialBalanceTest extends TestCase
{
    use DatabaseTransactions;

    private AccountingService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(AccountingService::class);
    }

    // ── 7.06  Trial balance is always balanced ────────────────────────────────

    public function test_trial_balance_totals_are_equal_after_posting_entries(): void
    {
        // Post several entries of different types to generate varied journal lines
        $this->svc->post('wallet_topup', ['amount' => 1000.00], [
            'reference_type' => 'WalletPayment', 'reference_id' => 901, 'user_id' => 10,
        ]);
        $this->svc->post('wallet_bonus', ['bonus_amount' => 200.00], [
            'reference_type' => 'WalletTransaction', 'reference_id' => 902, 'user_id' => 11,
        ]);
        $this->svc->post('referral_bonus_issued', ['bonus_amount' => 50.00], [
            'reference_type' => 'Order', 'reference_id' => 903, 'user_id' => 12,
        ]);

        $from = now()->toDateString();
        $to   = now()->toDateString();

        $tb = $this->svc->trialBalance($from, $to);

        // Core assertion: total debits must equal total credits
        $this->assertEqualsWithDelta(
            $tb['total_debit'],
            $tb['total_credit'],
            0.01,
            'Trial balance must be balanced: total debits must equal total credits'
        );
        $this->assertTrue($tb['balanced'], 'balanced flag must be true');
    }

    public function test_trial_balance_returns_expected_structure(): void
    {
        $this->svc->post('subscription_paid', ['subscription_amount' => 299.00], [
            'reference_type' => 'SubscriptionTransaction', 'reference_id' => 910, 'store_id' => 5,
        ]);

        $tb = $this->svc->trialBalance(now()->toDateString(), now()->toDateString());

        $this->assertArrayHasKey('rows',         $tb);
        $this->assertArrayHasKey('total_debit',  $tb);
        $this->assertArrayHasKey('total_credit', $tb);
        $this->assertArrayHasKey('balanced',     $tb);

        // Each row has the expected fields
        foreach ($tb['rows'] as $row) {
            $this->assertObjectHasProperty('account_code',   $row);
            $this->assertObjectHasProperty('account_name',   $row);
            $this->assertObjectHasProperty('type',           $row);
            $this->assertObjectHasProperty('normal_balance', $row);
            $this->assertObjectHasProperty('total_debit',    $row);
            $this->assertObjectHasProperty('total_credit',   $row);
            $this->assertObjectHasProperty('balance',        $row);
        }
    }

    // ── 7.02  General ledger running balance ──────────────────────────────────

    public function test_ledger_closing_balance_equals_opening_plus_movements(): void
    {
        // Post two wallet top-ups (DR 1013)
        $this->svc->post('wallet_topup', ['amount' => 300.00], ['user_id' => 20]);
        $this->svc->post('wallet_topup', ['amount' => 700.00], ['user_id' => 20]);

        // Locate account 1013
        $account = \Modules\Accounts\Entities\Account::where('code', '1013')->firstOrFail();

        $ledger = $this->svc->ledger($account->id, now()->toDateString(), now()->toDateString());

        $this->assertArrayHasKey('account',         $ledger);
        $this->assertArrayHasKey('opening_balance', $ledger);
        $this->assertArrayHasKey('rows',            $ledger);
        $this->assertArrayHasKey('closing_balance', $ledger);

        // Closing = opening + net movements within the period
        $periodDebit  = $ledger['rows']->sum('debit');
        $periodCredit = $ledger['rows']->sum('credit');
        $expectedClose = $ledger['opening_balance'] + $periodDebit - $periodCredit; // 1013 is debit-normal

        $this->assertEqualsWithDelta($expectedClose, $ledger['closing_balance'], 0.01);
    }
}
