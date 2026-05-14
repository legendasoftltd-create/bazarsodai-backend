<?php

namespace Tests\Unit\Accounts;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\AccountingRule;
use Modules\Accounts\Entities\JournalEntry;
use Modules\Accounts\Entities\JournalLine;
use Modules\Accounts\Exceptions\UnbalancedJournalException;
use Modules\Accounts\Services\AccountingService;
use Tests\TestCase;

class AccountingServiceTest extends TestCase
{
    // DatabaseTransactions wraps each test in a DB transaction that is
    // rolled back afterwards — no migrations needed, no data left behind.
    use DatabaseTransactions;

    private AccountingService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        // Chart of Accounts + Rules were seeded in Phase 1 and persist in the DB.
        $this->svc = app(AccountingService::class);
    }

    // ── 2.07  post() creates a balanced journal entry for order_completed ────

    public function test_post_order_completed_digital_creates_balanced_entry(): void
    {
        $data = [
            'order_amount'           => 500.000,
            'store_amount'           => 380.000,
            'admin_commission'       => 50.000,
            'additional_charge'      => 30.000,
            'extra_packaging_amount' => 10.000,
            'dm_delivery_share'      => 20.000,
            'delivery_fee_commission'=> 5.000,
            'tax_amount'             => 5.000,
            'order_id'               => 42,
            'store_id'               => 7,
        ];

        $entry = $this->svc->post('order_completed_digital', $data, [
            'reference_type' => 'Order',
            'reference_id'   => 42,
            'order_id'       => 42,
            'store_id'       => 7,
        ]);

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertEquals('posted', $entry->status);
        $this->assertEquals('order_completed_digital', $entry->event_type);

        $lines = $entry->lines;
        $this->assertGreaterThan(0, $lines->count());

        $debitTotal  = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEqualsWithDelta($debitTotal, $creditTotal, 0.001, 'Journal must balance');

        // Entry number format: JE-000001
        $this->assertMatchesRegularExpression('/^JE-\d{6}$/', $entry->entry_number);
    }

    public function test_post_stores_dimension_ids_on_lines(): void
    {
        $entry = $this->svc->post('cod_collected', ['amount' => 200.000], [
            'order_id'       => 99,
            'delivery_man_id'=> 5,
        ]);

        $entry->lines->each(function (JournalLine $line) {
            $this->assertEquals(99, $line->order_id);
            $this->assertEquals(5,  $line->delivery_man_id);
        });
    }

    public function test_post_skips_zero_amount_lines(): void
    {
        // tax_amount = 0 → the 2031 line must be omitted
        $data = [
            'order_amount'           => 400.000,
            'store_amount'           => 320.000,
            'admin_commission'       => 40.000,
            'additional_charge'      => 20.000,
            'extra_packaging_amount' => 10.000,
            'dm_delivery_share'      => 10.000,
            'delivery_fee_commission'=> 0.000,
            'tax_amount'             => 0.000,
        ];

        $entry = $this->svc->post('order_completed_digital', $data);

        $accountCodes = $entry->load('lines.account')->lines->map(fn($l) => $l->account->code);
        $this->assertNotContains('2031', $accountCodes->toArray());

        $debitTotal  = $entry->lines->sum('debit');
        $creditTotal = $entry->lines->sum('credit');
        $this->assertEqualsWithDelta($debitTotal, $creditTotal, 0.001, 'Entry must still balance after skipping zeros');
    }

    // ── 2.08  post() throws if lines don't balance ───────────────────────────

    public function test_post_throws_on_unbalanced_custom_rule(): void
    {
        // Inject a deliberately broken rule
        AccountingRule::create([
            'event_type'  => 'broken_test_event',
            'description' => 'intentionally unbalanced',
            'is_active'   => true,
            'lines'       => [
                ['account_code' => '1011', 'side' => 'debit',  'amount_field' => 'amount'],
                ['account_code' => '2011', 'side' => 'credit', 'amount_field' => 'different_amount'],
            ],
        ]);

        $this->expectException(UnbalancedJournalException::class);

        $this->svc->post('broken_test_event', ['amount' => 100, 'different_amount' => 99]);
    }

    // ── 2.09  reverse() creates a mirror entry linked to the original ────────

    public function test_reverse_creates_mirror_entry_with_reversal_of_id(): void
    {
        $original = $this->svc->post('cod_collected', ['amount' => 300.000], [
            'reference_type' => 'AccountTransaction',
            'reference_id'   => 1,
        ]);

        $reversal = $this->svc->reverse($original, 'Test reversal');

        // Status flags
        $this->assertEquals('reversed', $reversal->status);
        $this->assertEquals($original->id, $reversal->reversal_of_id);

        // Fresh from DB — original must also be marked reversed
        $this->assertEquals('reversed', $original->fresh()->status);

        // Lines are mirrored
        $origLines = $original->load('lines')->lines;
        $revLines  = $reversal->load('lines')->lines;
        $this->assertCount($origLines->count(), $revLines);

        foreach ($origLines as $i => $ol) {
            $rl = $revLines[$i];
            $this->assertEqualsWithDelta($ol->debit,  $rl->credit, 0.001);
            $this->assertEqualsWithDelta($ol->credit, $rl->debit,  0.001);
            $this->assertEquals($ol->account_id, $rl->account_id);
        }

        // Net effect: reversal + original lines should cancel to zero
        $netDebit  = $origLines->sum('debit')  + $revLines->sum('debit');
        $netCredit = $origLines->sum('credit') + $revLines->sum('credit');
        $this->assertEqualsWithDelta($netDebit, $netCredit, 0.001);
    }

    public function test_reverse_uses_distinct_entry_number(): void
    {
        $original = $this->svc->post('cod_collected', ['amount' => 100]);
        $reversal = $this->svc->reverse($original);

        $this->assertNotEquals($original->entry_number, $reversal->entry_number);
    }
}
