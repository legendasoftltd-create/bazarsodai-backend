<?php

namespace Tests\Unit\Accounts;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Accounts\Entities\JournalEntry;
use Modules\Accounts\Services\AccountingService;
use Tests\TestCase;

/**
 * Tests for Phase 4: COD collections and disbursements.
 *
 * These tests call AccountingService::post() directly with the same
 * arguments the controller hooks pass, verifying correct account routing
 * and balanced entries.
 */
class CodAndDisbursementTest extends TestCase
{
    use DatabaseTransactions;

    private AccountingService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(AccountingService::class);
    }

    // ── 4.04  COD collection ─────────────────────────────────────────────────

    public function test_cod_collected_dm_debits_cash_on_hand_and_clears_dm_receivable(): void
    {
        $entry = $this->svc->post('cod_collected', ['amount' => 350.000], [
            'reference_type'  => 'AccountTransaction',
            'reference_id'    => 101,
            'delivery_man_id' => 7,
        ]);

        $this->assertEquals('posted', $entry->status);
        $this->assertEquals('cod_collected', $entry->event_type);

        $entry->load('lines.account');

        // DR 1011 Cash on Hand
        $dr = $entry->lines->first(fn($l) => $l->debit > 0);
        $this->assertNotNull($dr);
        $this->assertEquals('1011', $dr->account->code, 'Cash on Hand (1011) must be debited');
        $this->assertEqualsWithDelta(350, $dr->debit, 0.001);

        // CR 1022 COD Receivable — Delivery Partners
        $cr = $entry->lines->first(fn($l) => $l->credit > 0);
        $this->assertNotNull($cr);
        $this->assertEquals('1022', $cr->account->code, 'COD Receivable DM (1022) must be credited');
        $this->assertEqualsWithDelta(350, $cr->credit, 0.001);

        // Balanced
        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    public function test_cod_collected_store_debits_cash_on_hand_and_clears_store_receivable(): void
    {
        $entry = $this->svc->post('cod_collected_store', ['amount' => 200.000], [
            'reference_type' => 'AccountTransaction',
            'reference_id'   => 102,
            'store_id'       => 3,
        ]);

        $entry->load('lines.account');

        $dr = $entry->lines->first(fn($l) => $l->debit > 0);
        $this->assertEquals('1011', $dr->account->code, 'Cash on Hand (1011) must be debited');

        $cr = $entry->lines->first(fn($l) => $l->credit > 0);
        $this->assertEquals('1023', $cr->account->code, 'COD Receivable Stores (1023) must be credited');

        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── 4.05  Store disbursement ─────────────────────────────────────────────

    public function test_store_disbursement_debits_store_wallet_payable_and_credits_bank(): void
    {
        $entry = $this->svc->post('store_disbursement', ['disbursement_amount' => 1500.000], [
            'reference_type' => 'DisbursementDetails',
            'reference_id'   => 55,
            'store_id'       => 4,
        ]);

        $this->assertEquals('store_disbursement', $entry->event_type);

        $entry->load('lines.account');

        $dr = $entry->lines->first(fn($l) => $l->debit > 0);
        $this->assertEquals('2011', $dr->account->code, 'Store Wallet Payable (2011) must be debited');
        $this->assertEqualsWithDelta(1500, $dr->debit, 0.001);

        $cr = $entry->lines->first(fn($l) => $l->credit > 0);
        $this->assertEquals('1012', $cr->account->code, 'Bank Settlement (1012) must be credited');
        $this->assertEqualsWithDelta(1500, $cr->credit, 0.001);

        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── 4.06  DM disbursement ────────────────────────────────────────────────

    public function test_dm_disbursement_debits_dm_wallet_payable_and_credits_bank(): void
    {
        $entry = $this->svc->post('dm_disbursement', ['disbursement_amount' => 800.000], [
            'reference_type'  => 'DisbursementDetails',
            'reference_id'    => 66,
            'delivery_man_id' => 12,
        ]);

        $this->assertEquals('dm_disbursement', $entry->event_type);

        $entry->load('lines.account');

        $dr = $entry->lines->first(fn($l) => $l->debit > 0);
        $this->assertEquals('2012', $dr->account->code, 'DM Wallet Payable (2012) must be debited');
        $this->assertEqualsWithDelta(800, $dr->debit, 0.001);

        $cr = $entry->lines->first(fn($l) => $l->credit > 0);
        $this->assertEquals('1012', $cr->account->code, 'Bank Settlement (1012) must be credited');
        $this->assertEqualsWithDelta(800, $cr->credit, 0.001);

        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── Dimension fields are stored on lines ─────────────────────────────────

    public function test_cod_collection_stores_delivery_man_id_on_lines(): void
    {
        $entry = $this->svc->post('cod_collected', ['amount' => 100], [
            'delivery_man_id' => 99,
        ]);

        $entry->lines->each(fn($l) => $this->assertEquals(99, $l->delivery_man_id));
    }

    public function test_store_disbursement_stores_store_id_on_lines(): void
    {
        $entry = $this->svc->post('store_disbursement', ['disbursement_amount' => 500], [
            'store_id' => 8,
        ]);

        $entry->lines->each(fn($l) => $this->assertEquals(8, $l->store_id));
    }
}
