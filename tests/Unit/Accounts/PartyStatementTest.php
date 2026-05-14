<?php

namespace Tests\Unit\Accounts;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Services\AccountingService;
use Tests\TestCase;

/**
 * Tests for Phase 9: store and DM party statements.
 */
class PartyStatementTest extends TestCase
{
    use DatabaseTransactions;

    private AccountingService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(AccountingService::class);
    }

    // ── 9.06  Store statement balance matches cumulative 2011 arithmetic ──────

    public function test_store_statement_closing_balance_equals_credits_minus_debits(): void
    {
        $storeId = 9901;

        // Post two orders credited to this store (CR 2011)
        $this->svc->postDirect('order_completed_digital', [
            ['account_code' => '1013', 'side' => 'debit',  'amount' => 300.00],
            ['account_code' => '2011', 'side' => 'credit', 'amount' => 240.00],
            ['account_code' => '4011', 'side' => 'credit', 'amount' =>  60.00],
        ], ['store_id' => $storeId, 'reference_type' => 'Order', 'reference_id' => 1001]);

        $this->svc->postDirect('order_completed_digital', [
            ['account_code' => '1013', 'side' => 'debit',  'amount' => 200.00],
            ['account_code' => '2011', 'side' => 'credit', 'amount' => 160.00],
            ['account_code' => '4011', 'side' => 'credit', 'amount' =>  40.00],
        ], ['store_id' => $storeId, 'reference_type' => 'Order', 'reference_id' => 1002]);

        // Post one disbursement to this store (DR 2011)
        $this->svc->postDirect('store_disbursement', [
            ['account_code' => '2011', 'side' => 'debit',  'amount' => 100.00],
            ['account_code' => '1012', 'side' => 'credit', 'amount' => 100.00],
        ], ['store_id' => $storeId, 'reference_type' => 'DisbursementDetails', 'reference_id' => 501]);

        $from = now()->toDateString();
        $to   = now()->toDateString();

        $statement = $this->svc->storeStatement($storeId, $from, $to);

        // Closing balance must equal credits - debits on account 2011 for this store
        $account = Account::where('code', '2011')->firstOrFail();
        $totals  = \Illuminate\Support\Facades\DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $account->id)
            ->where('jl.store_id', $storeId)
            ->where('je.status', 'posted')
            ->selectRaw('COALESCE(SUM(jl.credit), 0) as c, COALESCE(SUM(jl.debit), 0) as d')
            ->first();

        $expectedBalance = (float)$totals->c - (float)$totals->d; // 240 + 160 - 100 = 300

        $this->assertEqualsWithDelta($expectedBalance, $statement['closing_balance'], 0.01);
        $this->assertEqualsWithDelta(300.00, $statement['closing_balance'], 0.01);
        $this->assertCount(3, $statement['rows']);
    }

    public function test_store_statement_running_balance_is_cumulative(): void
    {
        $storeId = 9902;

        $this->svc->postDirect('order_completed_digital', [
            ['account_code' => '1013', 'side' => 'debit',  'amount' => 500.00],
            ['account_code' => '2011', 'side' => 'credit', 'amount' => 400.00],
            ['account_code' => '4011', 'side' => 'credit', 'amount' => 100.00],
        ], ['store_id' => $storeId]);

        $statement = $this->svc->storeStatement($storeId, now()->toDateString(), now()->toDateString());

        // Last row's running_balance == closing_balance
        $lastRow = $statement['rows']->last();
        $this->assertEqualsWithDelta($statement['closing_balance'], $lastRow->running_balance, 0.01);
    }

    public function test_dm_statement_closing_balance_equals_credits_minus_debits(): void
    {
        $dmId = 8801;

        // Post a delivery fee earned (CR 2012)
        $this->svc->postDirect('order_completed_cod', [
            ['account_code' => '1022', 'side' => 'debit',  'amount' => 500.00],
            ['account_code' => '2011', 'side' => 'credit', 'amount' => 380.00],
            ['account_code' => '4011', 'side' => 'credit', 'amount' =>  40.00],
            ['account_code' => '2012', 'side' => 'credit', 'amount' =>  80.00],
        ], ['delivery_man_id' => $dmId, 'reference_type' => 'Order', 'reference_id' => 2001]);

        // Post a disbursement to DM (DR 2012)
        $this->svc->postDirect('dm_disbursement', [
            ['account_code' => '2012', 'side' => 'debit',  'amount' => 50.00],
            ['account_code' => '1012', 'side' => 'credit', 'amount' => 50.00],
        ], ['delivery_man_id' => $dmId]);

        $statement = $this->svc->dmStatement($dmId, now()->toDateString(), now()->toDateString());

        // 80 credited - 50 debited = 30 outstanding
        $this->assertEqualsWithDelta(30.00, $statement['closing_balance'], 0.01);
        $this->assertCount(2, $statement['rows']);
    }

    public function test_store_statement_returns_expected_keys(): void
    {
        $statement = $this->svc->storeStatement(99999, now()->toDateString(), now()->toDateString());

        $this->assertArrayHasKey('party_id',        $statement);
        $this->assertArrayHasKey('account',          $statement);
        $this->assertArrayHasKey('opening_balance',  $statement);
        $this->assertArrayHasKey('rows',             $statement);
        $this->assertArrayHasKey('closing_balance',  $statement);
        $this->assertEquals(0.0, $statement['closing_balance']); // no entries for unknown store
    }
}
