<?php

namespace Tests\Unit\Accounts;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Accounts\Services\AccountingService;
use Tests\TestCase;

/**
 * Tests for Phase 5: wallet top-ups, bonuses, loyalty redemptions,
 * referral bonuses, and subscription payments.
 */
class WalletAndSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    private AccountingService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(AccountingService::class);
    }

    // ── 5.07  Wallet top-up (gateway) ────────────────────────────────────────

    public function test_wallet_topup_debits_gateway_clearing_and_credits_customer_wallet(): void
    {
        $entry = $this->svc->post('wallet_topup', ['amount' => 500.000], [
            'reference_type' => 'WalletPayment',
            'reference_id'   => 10,
            'user_id'        => 42,
        ]);

        $this->assertEquals('posted', $entry->status);
        $this->assertEquals('wallet_topup', $entry->event_type);

        $entry->load('lines.account');

        // DR 1013 Gateway Clearing
        $dr = $entry->lines->first(fn($l) => $l->debit > 0);
        $this->assertNotNull($dr);
        $this->assertEquals('1013', $dr->account->code, 'Gateway Clearing (1013) must be debited');
        $this->assertEqualsWithDelta(500, $dr->debit, 0.001);

        // CR 2021 Customer Wallet Liability
        $cr = $entry->lines->first(fn($l) => $l->credit > 0);
        $this->assertNotNull($cr);
        $this->assertEquals('2021', $cr->account->code, 'Customer Wallet Liability (2021) must be credited');
        $this->assertEqualsWithDelta(500, $cr->credit, 0.001);

        // Balanced
        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── 5.02  Admin wallet bonus ──────────────────────────────────────────────

    public function test_wallet_bonus_debits_platform_advance_and_credits_customer_wallet(): void
    {
        $entry = $this->svc->post('wallet_bonus', ['bonus_amount' => 100.000], [
            'reference_type' => 'WalletTransaction',
            'reference_id'   => 20,
            'user_id'        => 5,
        ]);

        $this->assertEquals('wallet_bonus', $entry->event_type);

        $entry->load('lines.account');

        // DR 1031 Platform Advance (manual received)
        $dr = $entry->lines->first(fn($l) => $l->debit > 0);
        $this->assertNotNull($dr);
        $this->assertEquals('1031', $dr->account->code, 'Platform Advance (1031) must be debited');
        $this->assertEqualsWithDelta(100, $dr->debit, 0.001);

        // CR 2021 Customer Wallet Liability
        $cr = $entry->lines->first(fn($l) => $l->credit > 0);
        $this->assertNotNull($cr);
        $this->assertEquals('2021', $cr->account->code, 'Customer Wallet Liability (2021) must be credited');
        $this->assertEqualsWithDelta(100, $cr->credit, 0.001);

        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── 5.03  Loyalty point redemption ───────────────────────────────────────

    public function test_loyalty_point_redeemed_debits_loyalty_expense_and_credits_points_liability(): void
    {
        $entry = $this->svc->post('loyalty_point_redeemed', ['redemption_value' => 25.000], [
            'reference_type' => 'WalletTransaction',
            'reference_id'   => 30,
            'user_id'        => 7,
        ]);

        $this->assertEquals('loyalty_point_redeemed', $entry->event_type);

        $entry->load('lines.account');

        // DR 5022 Loyalty Redemption Expense
        $dr = $entry->lines->first(fn($l) => $l->debit > 0);
        $this->assertNotNull($dr);
        $this->assertEquals('5022', $dr->account->code, 'Loyalty Redemption Expense (5022) must be debited');
        $this->assertEqualsWithDelta(25, $dr->debit, 0.001);

        // CR 2022 Loyalty Points Liability
        $cr = $entry->lines->first(fn($l) => $l->credit > 0);
        $this->assertNotNull($cr);
        $this->assertEquals('2022', $cr->account->code, 'Loyalty Points Liability (2022) must be credited');
        $this->assertEqualsWithDelta(25, $cr->credit, 0.001);

        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── 5.04  Referral bonus ──────────────────────────────────────────────────

    public function test_referral_bonus_issued_debits_referral_expense_and_credits_customer_wallet(): void
    {
        $entry = $this->svc->post('referral_bonus_issued', ['bonus_amount' => 50.000], [
            'reference_type' => 'Order',
            'reference_id'   => 99,
            'user_id'        => 12,
        ]);

        $this->assertEquals('referral_bonus_issued', $entry->event_type);

        $entry->load('lines.account');

        // DR 5021 Referral Discount Expense
        $dr = $entry->lines->first(fn($l) => $l->debit > 0);
        $this->assertNotNull($dr);
        $this->assertEquals('5021', $dr->account->code, 'Referral Discount Expense (5021) must be debited');
        $this->assertEqualsWithDelta(50, $dr->debit, 0.001);

        // CR 2021 Customer Wallet Liability
        $cr = $entry->lines->first(fn($l) => $l->credit > 0);
        $this->assertNotNull($cr);
        $this->assertEquals('2021', $cr->account->code, 'Customer Wallet Liability (2021) must be credited');
        $this->assertEqualsWithDelta(50, $cr->credit, 0.001);

        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── 5.08  Subscription paid ───────────────────────────────────────────────

    public function test_subscription_paid_debits_gateway_clearing_and_credits_subscription_revenue(): void
    {
        $entry = $this->svc->post('subscription_paid', ['subscription_amount' => 299.000], [
            'reference_type' => 'SubscriptionTransaction',
            'reference_id'   => 55,
            'store_id'       => 3,
        ]);

        $this->assertEquals('posted', $entry->status);
        $this->assertEquals('subscription_paid', $entry->event_type);

        $entry->load('lines.account');

        // DR 1013 Gateway Clearing
        $dr = $entry->lines->first(fn($l) => $l->debit > 0);
        $this->assertNotNull($dr);
        $this->assertEquals('1013', $dr->account->code, 'Gateway Clearing (1013) must be debited');
        $this->assertEqualsWithDelta(299, $dr->debit, 0.001);

        // CR 4030 Subscription Revenue
        $cr = $entry->lines->first(fn($l) => $l->credit > 0);
        $this->assertNotNull($cr);
        $this->assertEquals('4030', $cr->account->code, 'Subscription Revenue (4030) must be credited');
        $this->assertEqualsWithDelta(299, $cr->credit, 0.001);

        // Balanced
        $this->assertEqualsWithDelta($entry->lines->sum('debit'), $entry->lines->sum('credit'), 0.001);
    }

    // ── user_id dimension stored on lines ─────────────────────────────────────

    public function test_wallet_topup_stores_user_id_on_lines(): void
    {
        $entry = $this->svc->post('wallet_topup', ['amount' => 200], [
            'user_id' => 77,
        ]);

        $entry->lines->each(fn($l) => $this->assertEquals(77, $l->user_id));
    }
}
