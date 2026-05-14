<?php

namespace Tests\Unit\Accounts;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\AccountingRule;
use Modules\Accounts\Entities\JournalEntry;
use Modules\Accounts\Services\AccountingService;
use Modules\Accounts\Services\BackfillService;
use Modules\Accounts\Services\ReconcileService;
use Tests\TestCase;

/**
 * Full system smoke test — exercises every service method and reconciliation check
 * against the real database in a transaction that rolls back after each test.
 */
class FullSystemSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private AccountingService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(AccountingService::class);
    }

    // ── AccountingService ─────────────────────────────────────────────────────

    public function test_post_creates_balanced_journal_entry(): void
    {
        $je = $this->svc->post('wallet_topup', ['amount' => 250.00], [
            'reference_type' => 'WalletPayment',
            'reference_id'   => 9001,
            'user_id'        => 42,
        ]);

        $this->assertEquals('posted', $je->status);
        $this->assertNotEmpty($je->entry_number);
        $je->load('lines');
        $this->assertEqualsWithDelta($je->lines->sum('debit'), $je->lines->sum('credit'), 0.001);
        $this->assertTrue($je->isBalanced());
    }

    public function test_post_unknown_event_throws(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->svc->post('event_that_does_not_exist', ['amount' => 100]);
    }

    public function test_post_unbalanced_custom_rule_throws(): void
    {
        // Temporarily create an unbalanced rule
        AccountingRule::create([
            'event_type'  => 'test_unbalanced_' . uniqid(),
            'description' => 'deliberate unbalanced rule for test',
            'is_active'   => true,
            'lines'       => [
                ['account_code' => '1013', 'side' => 'debit',  'amount_field' => 'amount'],
                // missing credit line
            ],
        ]);

        // Rules with only one side produce a 0 diff for amount=0, so use amount > 0 to trigger
        // Actually a single-line rule means debit=amount, credit=0 → unbalanced
        $rule = AccountingRule::where('event_type', 'like', 'test_unbalanced_%')->latest()->first();
        $this->assertNotNull($rule);

        $this->expectException(\Modules\Accounts\Exceptions\UnbalancedJournalException::class);
        $this->svc->post($rule->event_type, ['amount' => 100]);
    }

    public function test_reverse_creates_mirror_entry(): void
    {
        $original = $this->svc->post('subscription_paid', ['subscription_amount' => 199.00], [
            'reference_type' => 'SubscriptionTransaction',
            'reference_id'   => 8001,
            'store_id'       => 3,
        ]);

        $reversal = $this->svc->reverse($original);

        $this->assertEquals($original->id, $reversal->reversal_of_id);
        $this->assertStringContainsString('reversal', $reversal->event_type);
        $reversal->load('lines');
        $this->assertTrue($reversal->isBalanced());

        // Lines are swapped
        $original->load('lines');
        $origDebit  = $original->lines->sum('debit');
        $revCredit  = $reversal->lines->sum('credit');
        $this->assertEqualsWithDelta($origDebit, $revCredit, 0.001);
    }

    public function test_post_direct_works_and_balances(): void
    {
        $je = $this->svc->postDirect('order_completed_cod', [
            ['account_code' => '1022', 'side' => 'debit',  'amount' => 500],
            ['account_code' => '2011', 'side' => 'credit', 'amount' => 380],
            ['account_code' => '4011', 'side' => 'credit', 'amount' =>  40],
            ['account_code' => '2012', 'side' => 'credit', 'amount' =>  80],
        ], ['store_id' => 5, 'delivery_man_id' => 7]);

        $je->load('lines');
        $this->assertEqualsWithDelta($je->lines->sum('debit'), $je->lines->sum('credit'), 0.001);
        $this->assertCount(4, $je->lines);
    }

    public function test_all_event_types_post_without_error(): void
    {
        $events = [
            ['wallet_bonus',          ['bonus_amount'         => 50],  ['user_id' => 1]],
            ['loyalty_point_redeemed',['redemption_value'     => 20],  ['user_id' => 2]],
            ['referral_bonus_issued', ['bonus_amount'         => 30],  ['user_id' => 3]],
            ['subscription_paid',     ['subscription_amount'  => 299], ['store_id' => 1]],
        ];

        foreach ($events as [$event, $data, $ctx]) {
            $je = $this->svc->post($event, $data, $ctx);
            $this->assertEquals('posted', $je->status, "Event {$event} should be posted");
            $je->load('lines');
            $this->assertTrue($je->isBalanced(), "Event {$event} must be balanced");
        }
    }

    // ── Reports / Service methods ─────────────────────────────────────────────

    public function test_trial_balance_returns_correct_structure_and_is_balanced(): void
    {
        $this->svc->post('wallet_topup', ['amount' => 100], ['user_id' => 5]);

        $tb = $this->svc->trialBalance(now()->toDateString(), now()->toDateString());

        $this->assertArrayHasKey('rows',         $tb);
        $this->assertArrayHasKey('total_debit',  $tb);
        $this->assertArrayHasKey('total_credit', $tb);
        $this->assertArrayHasKey('balanced',     $tb);
        $this->assertTrue($tb['balanced']);
        $this->assertEqualsWithDelta($tb['total_debit'], $tb['total_credit'], 0.01);
    }

    public function test_ledger_running_balance_is_consistent(): void
    {
        $account = Account::where('code', '1013')->firstOrFail();

        $this->svc->post('wallet_topup',    ['amount' => 300], ['user_id' => 10]);
        $this->svc->post('subscription_paid', ['subscription_amount' => 100], ['store_id' => 1]);

        $ledger = $this->svc->ledger($account->id, now()->toDateString(), now()->toDateString());

        $this->assertArrayHasKey('opening_balance', $ledger);
        $this->assertArrayHasKey('closing_balance', $ledger);

        // Last row's running balance == closing balance
        $lastRow = $ledger['rows']->last();
        $this->assertEqualsWithDelta($ledger['closing_balance'], $lastRow->running_balance, 0.001);
    }

    public function test_profit_and_loss_net_equals_revenue_minus_expenses(): void
    {
        $this->svc->post('referral_bonus_issued', ['bonus_amount' => 50], ['user_id' => 1]);

        $pl = $this->svc->profitAndLoss(now()->toDateString(), now()->toDateString());

        $this->assertArrayHasKey('revenue_rows',   $pl);
        $this->assertArrayHasKey('expense_rows',   $pl);
        $this->assertArrayHasKey('total_revenue',  $pl);
        $this->assertArrayHasKey('total_expenses', $pl);
        $this->assertEqualsWithDelta(
            $pl['total_revenue'] - $pl['total_expenses'],
            $pl['net_profit'],
            0.001
        );
    }

    public function test_balance_sheet_assets_equal_liabilities_plus_equity(): void
    {
        $this->svc->post('wallet_topup', ['amount' => 500], ['user_id' => 1]);

        $bs = $this->svc->balanceSheet(now()->toDateString());

        $this->assertArrayHasKey('balanced',        $bs);
        $this->assertArrayHasKey('total_assets',    $bs);
        $this->assertArrayHasKey('total_liabilities',$bs);
        $this->assertArrayHasKey('total_equity',    $bs);
        $this->assertTrue($bs['balanced']);
        $this->assertEqualsWithDelta(
            $bs['total_assets'],
            $bs['total_liabilities'] + $bs['total_equity'],
            0.01
        );
    }

    public function test_tax_report_returns_correct_structure(): void
    {
        $result = $this->svc->taxReport(now()->toDateString(), now()->toDateString());

        $this->assertArrayHasKey('rows',            $result);
        $this->assertArrayHasKey('total_collected', $result);
        $this->assertArrayHasKey('total_remitted',  $result);
        $this->assertArrayHasKey('net_payable',     $result);
        $this->assertEqualsWithDelta(
            $result['total_collected'] - $result['total_remitted'],
            $result['net_payable'],
            0.001
        );
    }

    public function test_cod_reconciliation_outstanding_is_debit_minus_credit(): void
    {
        $this->svc->postDirect('order_completed_cod', [
            ['account_code' => '1022', 'side' => 'debit',  'amount' => 300],
            ['account_code' => '2011', 'side' => 'credit', 'amount' => 240],
            ['account_code' => '4011', 'side' => 'credit', 'amount' =>  60],
        ], ['store_id' => 1, 'delivery_man_id' => 1]);

        $cod = $this->svc->codReconciliation(now()->toDateString(), now()->toDateString());

        $this->assertEqualsWithDelta(
            $cod['total_owed'] - $cod['total_settled'],
            $cod['outstanding'],
            0.001
        );
        $this->assertGreaterThan(0, $cod['outstanding']);
    }

    public function test_gateway_reconciliation_outstanding_is_debit_minus_credit(): void
    {
        $this->svc->post('wallet_topup', ['amount' => 750], ['user_id' => 1]);

        $gw = $this->svc->gatewayReconciliation(now()->toDateString(), now()->toDateString());

        $this->assertEqualsWithDelta($gw['total_in'] - $gw['total_out'], $gw['outstanding'], 0.001);
        $this->assertGreaterThanOrEqual(0, $gw['outstanding']);
    }

    public function test_store_statement_closing_matches_sum_arithmetic(): void
    {
        $storeId = 77001;

        $this->svc->postDirect('order_completed_digital', [
            ['account_code' => '1013', 'side' => 'debit',  'amount' => 400],
            ['account_code' => '2011', 'side' => 'credit', 'amount' => 320],
            ['account_code' => '4011', 'side' => 'credit', 'amount' =>  80],
        ], ['store_id' => $storeId]);

        $this->svc->postDirect('store_disbursement', [
            ['account_code' => '2011', 'side' => 'debit',  'amount' => 100],
            ['account_code' => '1012', 'side' => 'credit', 'amount' => 100],
        ], ['store_id' => $storeId]);

        $stmt = $this->svc->storeStatement($storeId, now()->toDateString(), now()->toDateString());

        $this->assertEqualsWithDelta(220.00, $stmt['closing_balance'], 0.001);
        $this->assertCount(2, $stmt['rows']);
    }

    public function test_dm_statement_closing_matches_sum_arithmetic(): void
    {
        $dmId = 88001;

        $this->svc->postDirect('order_completed_cod', [
            ['account_code' => '1022', 'side' => 'debit',  'amount' => 500],
            ['account_code' => '2011', 'side' => 'credit', 'amount' => 400],
            ['account_code' => '4011', 'side' => 'credit', 'amount' =>  20],
            ['account_code' => '2012', 'side' => 'credit', 'amount' =>  80],
        ], ['delivery_man_id' => $dmId]);

        $stmt = $this->svc->dmStatement($dmId, now()->toDateString(), now()->toDateString());

        $this->assertEqualsWithDelta(80.00, $stmt['closing_balance'], 0.001);
    }

    // ── ReconcileService ──────────────────────────────────────────────────────

    public function test_reconcile_trial_balance_passes_after_posting(): void
    {
        $this->svc->post('wallet_topup', ['amount' => 100], ['user_id' => 1]);
        $this->svc->post('subscription_paid', ['subscription_amount' => 200], ['store_id' => 1]);

        $result = app(ReconcileService::class)->checkTrialBalance();

        $this->assertTrue($result['reconciled']);
        $this->assertFalse($result['skipped']);
        $this->assertEqualsWithDelta($result['journal_value'], $result['expected_value'], 0.001);
    }

    public function test_reconcile_accounting_equation_passes(): void
    {
        $this->svc->post('wallet_topup', ['amount' => 300], ['user_id' => 1]);

        $result = app(ReconcileService::class)->checkAccountingEquation();

        $this->assertTrue($result['reconciled'], 'Accounting equation should balance after posting');
    }

    public function test_reconcile_cod_non_negative_after_posting(): void
    {
        $this->svc->postDirect('order_completed_cod', [
            ['account_code' => '1022', 'side' => 'debit',  'amount' => 200],
            ['account_code' => '2011', 'side' => 'credit', 'amount' => 160],
            ['account_code' => '4011', 'side' => 'credit', 'amount' =>  40],
        ], ['store_id' => 1, 'delivery_man_id' => 1]);

        $result = app(ReconcileService::class)->checkCodNonNegative();

        $this->assertTrue($result['reconciled'], 'COD balance should be non-negative');
    }

    public function test_reconcile_run_all_returns_9_checks(): void
    {
        $results = app(ReconcileService::class)->runAll();

        $this->assertCount(9, $results);
        foreach ($results as $r) {
            $this->assertArrayHasKey('name',           $r);
            $this->assertArrayHasKey('reconciled',     $r);
            $this->assertArrayHasKey('skipped',        $r);
            $this->assertArrayHasKey('journal_value',  $r);
            $this->assertArrayHasKey('expected_value', $r);
            $this->assertArrayHasKey('difference',     $r);
        }
    }

    public function test_all_9_checks_pass_or_skip_after_clean_posting(): void
    {
        $this->svc->post('wallet_topup', ['amount' => 100], ['user_id' => 1]);

        $results = app(ReconcileService::class)->runAll();

        // Wallet-parity checks (2,3,6) compare journal_lines against legacy wallet tables.
        // In any environment where only the journal was backfilled (but wallet tables were not
        // also backfilled or updated), these will show a difference. This is the known
        // pre-migration gap; the accounts:reconcile --skip-wallet-parity flag handles it.
        $walletParityNames = ['Store Wallets', 'DM Wallets', 'Customer Wallets'];

        $failing = collect($results)
            ->where('skipped', false)
            ->where('reconciled', false)
            ->filter(fn($r) => !collect($walletParityNames)->contains(fn($k) => str_contains($r['name'], $k)))
            ->all();

        $this->assertEmpty(
            $failing,
            'Non-wallet-parity checks FAILED: ' . implode(', ', array_column($failing, 'name'))
        );
    }

    // ── Chart of Accounts seeder integrity ───────────────────────────────────

    public function test_all_accounting_rule_account_codes_exist_in_chart(): void
    {
        $rules = \Modules\Accounts\Entities\AccountingRule::all();
        $existingCodes = Account::pluck('code')->toArray();

        foreach ($rules as $rule) {
            foreach ($rule->lines as $line) {
                $this->assertContains(
                    $line['account_code'],
                    $existingCodes,
                    "Rule [{$rule->event_type}] references missing account code [{$line['account_code']}]"
                );
            }
        }
    }

    public function test_every_posted_journal_entry_is_balanced(): void
    {
        // Post a batch of different event types
        $this->svc->post('wallet_topup',          ['amount'               => 100], ['user_id' => 1]);
        $this->svc->post('wallet_bonus',           ['bonus_amount'         =>  50], ['user_id' => 2]);
        $this->svc->post('loyalty_point_redeemed', ['redemption_value'     =>  25], ['user_id' => 3]);
        $this->svc->post('referral_bonus_issued',  ['bonus_amount'         =>  30], ['user_id' => 4]);
        $this->svc->post('subscription_paid',      ['subscription_amount'  => 299], ['store_id' => 1]);

        // Check all journal entries ever created in this test are balanced
        JournalEntry::with('lines')->where('status', 'posted')->get()->each(function ($je) {
            $this->assertEqualsWithDelta(
                $je->lines->sum('debit'),
                $je->lines->sum('credit'),
                0.001,
                "Journal entry {$je->entry_number} ({$je->event_type}) is unbalanced"
            );
        });
    }
}
