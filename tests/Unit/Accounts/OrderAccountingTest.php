<?php

namespace Tests\Unit\Accounts;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Accounts\Entities\JournalEntry;
use Modules\Accounts\Entities\JournalLine;
use Modules\Accounts\Services\AccountingService;
use Tests\TestCase;

/**
 * Tests for Phase 3: Order Integration.
 *
 * These tests drive AccountingService::postDirect() with the same $journalLines
 * array that OrderLogic::create_transaction() assembles, verifying:
 *  - correct DR account for each payment method (3.04 / 3.05 / 3.06)
 *  - balanced entries in all scenarios
 *  - reversal creates a mirror entry with reversal_of_id set (3.07)
 */
class OrderAccountingTest extends TestCase
{
    use DatabaseTransactions;

    private AccountingService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(AccountingService::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Build the $journalLines array the same way OrderLogic::create_transaction()
     * does, given the pre-computed amounts.
     */
    private function buildOrderLines(
        string $paymentMethod,
        float  $orderAmount,
        float  $storeAmount,
        float  $adminCommissionNet,
        float  $dmShareTotal       = 0,
        float  $flashAdminDiscount = 0
    ): array {
        $drAccount = match($paymentMethod) {
            'cash_on_delivery' => '1022',
            'wallet'           => '2021',
            default            => '1013',
        };

        $lines = [
            ['account_code' => $drAccount, 'side' => 'debit',  'amount' => $orderAmount],
            ['account_code' => '2011',     'side' => 'credit', 'amount' => $storeAmount],
            ['account_code' => '4011',     'side' => $adminCommissionNet >= 0 ? 'credit' : 'debit', 'amount' => abs($adminCommissionNet)],
        ];

        if ($dmShareTotal > 0) {
            $lines[] = ['account_code' => '2012', 'side' => 'credit', 'amount' => $dmShareTotal];
        }
        if ($flashAdminDiscount > 0) {
            $lines[] = ['account_code' => '5012', 'side' => 'debit', 'amount' => $flashAdminDiscount];
        }

        return $lines;
    }

    // ── 3.04  Digital payment ─────────────────────────────────────────────────

    public function test_digital_order_debits_gateway_clearing_account(): void
    {
        // 500 order: 10% commission, delivery 50 (2.5% delivery fee comm), tax 20, add 10, pkg 5
        $lines = $this->buildOrderLines('digital_payment', 500, 398.5, 54, 47.5);

        $entry = $this->svc->postDirect('order_completed_digital', $lines, [
            'reference_type' => 'Order', 'reference_id' => 1001,
            'order_id' => 1001, 'store_id' => 5,
        ]);

        $this->assertEquals('posted', $entry->status);
        $this->assertEquals('order_completed_digital', $entry->event_type);

        // DR account must be 1013 (Gateway Clearing)
        $drLine = $entry->load('lines.account')->lines->first(fn($l) => $l->debit > 0 && $l->account->code === '1013');
        $this->assertNotNull($drLine, 'Gateway Clearing (1013) should be debited for digital payment');
        $this->assertEqualsWithDelta(500, $drLine->debit, 0.001);

        // Entry must balance
        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    public function test_digital_order_credits_store_wallet_payable(): void
    {
        $lines = $this->buildOrderLines('digital_payment', 500, 398.5, 54, 47.5);
        $entry = $this->svc->postDirect('order_completed_digital', $lines);

        $crStore = $entry->load('lines.account')->lines->first(fn($l) => $l->account->code === '2011');
        $this->assertNotNull($crStore, 'Store Wallet Payable (2011) should be credited');
        $this->assertEqualsWithDelta(398.5, $crStore->credit, 0.001);
    }

    // ── 3.05  COD order ──────────────────────────────────────────────────────

    public function test_cod_order_debits_cod_receivable_not_gateway(): void
    {
        // Same amounts but payment_method = COD
        $lines = $this->buildOrderLines('cash_on_delivery', 500, 398.5, 54, 47.5);

        $entry = $this->svc->postDirect('order_completed_cod', $lines, [
            'reference_type' => 'Order', 'reference_id' => 1002,
        ]);

        $entry->load('lines.account');

        $drLine1022 = $entry->lines->first(fn($l) => $l->debit > 0 && $l->account->code === '1022');
        $this->assertNotNull($drLine1022, 'COD Receivable (1022) should be debited for COD order');

        $drLine1013 = $entry->lines->first(fn($l) => $l->debit > 0 && $l->account->code === '1013');
        $this->assertNull($drLine1013, 'Gateway Clearing (1013) must NOT be debited for COD');

        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── 3.06  Wallet order ───────────────────────────────────────────────────

    public function test_wallet_order_debits_customer_wallet_payable(): void
    {
        $lines = $this->buildOrderLines('wallet', 300, 230, 30, 40);

        $entry = $this->svc->postDirect('order_completed_wallet', $lines, [
            'reference_type' => 'Order', 'reference_id' => 1003,
        ]);

        $entry->load('lines.account');

        $drLine2021 = $entry->lines->first(fn($l) => $l->debit > 0 && $l->account->code === '2021');
        $this->assertNotNull($drLine2021, 'Customer Wallet Payable (2021) should be debited for wallet order');

        $drLine1013 = $entry->lines->first(fn($l) => $l->debit > 0 && $l->account->code === '1013');
        $this->assertNull($drLine1013, 'Gateway (1013) must NOT be debited for wallet order');

        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── Flash admin discount ─────────────────────────────────────────────────

    public function test_flash_admin_discount_adds_expense_dr_and_still_balances(): void
    {
        // Customer paid 80, admin flash discount = 20, no delivery
        // store gets 90 (on gross 100), admin commission = 10
        $lines = $this->buildOrderLines('digital_payment', 80, 90, 10, 0, 20);

        $entry = $this->svc->postDirect('order_completed_digital', $lines);

        $entry->load('lines.account');
        $flashLine = $entry->lines->first(fn($l) => $l->debit > 0 && $l->account->code === '5012');
        $this->assertNotNull($flashLine, 'Flash Sale Admin Subsidy (5012) should be debited');
        $this->assertEqualsWithDelta(20, $flashLine->debit, 0.001);

        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── 3.07  Refund creates reversal ────────────────────────────────────────

    public function test_refund_creates_reversal_entry_with_reversal_of_id(): void
    {
        // Post the original order entry
        $lines = $this->buildOrderLines('digital_payment', 500, 398.5, 54, 47.5);
        $original = $this->svc->postDirect('order_completed_digital', $lines, [
            'reference_type' => 'Order', 'reference_id' => 2001,
        ]);

        // Refund it
        $reversal = $this->svc->reverse($original, 'Order #2001 refunded');

        $this->assertEquals($original->id, $reversal->reversal_of_id);
        $this->assertEquals('reversed', $reversal->status);
        $this->assertStringContainsString('_reversal', $reversal->event_type);

        // Original is now marked reversed
        $this->assertEquals('reversed', $original->fresh()->status);

        // Every line is mirrored
        $origLines = $original->load('lines')->lines;
        $revLines  = $reversal->load('lines')->lines;
        $this->assertCount($origLines->count(), $revLines);

        foreach ($origLines as $i => $ol) {
            $rl = $revLines[$i];
            $this->assertEqualsWithDelta($ol->debit,  $rl->credit, 0.001);
            $this->assertEqualsWithDelta($ol->credit, $rl->debit,  0.001);
        }

        // Net position cancels to zero
        $allLines = $origLines->concat($revLines);
        $this->assertEqualsWithDelta(0, $allLines->sum('debit') - $allLines->sum('credit'), 0.001);
    }

    public function test_refund_entry_has_unique_entry_number(): void
    {
        $lines    = $this->buildOrderLines('digital_payment', 200, 150, 30, 20);
        $original = $this->svc->postDirect('order_completed_digital', $lines);
        $reversal = $this->svc->reverse($original);

        $this->assertNotEquals($original->entry_number, $reversal->entry_number);
    }
}
