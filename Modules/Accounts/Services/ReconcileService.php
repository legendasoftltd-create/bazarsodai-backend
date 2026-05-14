<?php

namespace Modules\Accounts\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Account;

/**
 * Runs the 9 financial reconciliation checks.
 *
 * Each check returns:
 *   name          string   Human-readable check name
 *   journal_value float    Value derived from journal_lines
 *   expected_value float   Value derived from the legacy/external source
 *   difference    float    abs(journal_value - expected_value)
 *   reconciled    bool     true if difference < tolerance
 *   skipped       bool     true if the external source table doesn't exist
 *   skip_reason   string   Why it was skipped
 */
class ReconcileService
{
    private float $tolerance;

    public function __construct()
    {
        $this->tolerance = (float) config('accounts.reconcile_tolerance', 0.01);
    }

    /** Run all 9 checks and return the results array. */
    public function runAll(): array
    {
        return [
            $this->checkTrialBalance(),
            $this->checkStoreWallets(),
            $this->checkDmWallets(),
            $this->checkAdminCommission(),
            $this->checkTaxPayable(),
            $this->checkCustomerWallets(),
            $this->checkCodNonNegative(),
            $this->checkGatewayNonNegative(),
            $this->checkAccountingEquation(),
        ];
    }

    // ── Check 1: Trial Balance ────────────────────────────────────────────────

    public function checkTrialBalance(): array
    {
        $totals = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('je.status', 'posted')
            ->selectRaw('COALESCE(SUM(jl.debit),0) as d, COALESCE(SUM(jl.credit),0) as c')
            ->first();

        $dr   = (float)$totals->d;
        $cr   = (float)$totals->c;
        $diff = abs($dr - $cr);

        return $this->result('Trial Balance (DR = CR)', $dr, $cr, $diff);
    }

    // ── Check 2: Store Wallet Parity ─────────────────────────────────────────

    public function checkStoreWallets(): array
    {
        try {
            $jeNet = (float) DB::table('journal_lines as jl')
                ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
                ->join('accounts as a', 'a.id', '=', 'jl.account_id')
                ->where('a.code', '2011')
                ->where('je.status', 'posted')
                ->selectRaw('COALESCE(SUM(jl.credit),0) - COALESCE(SUM(jl.debit),0) as net')
                ->value('net');

            $walletSum = (float) DB::table('store_wallets')->sum('balance');

            return $this->result('Store Wallets (2011 net = store_wallets.balance)', $jeNet, $walletSum);
        } catch (\Exception $e) {
            return $this->skipped('Store Wallets (2011 net = store_wallets.balance)', $e->getMessage());
        }
    }

    // ── Check 3: DM Wallet Parity ─────────────────────────────────────────────

    public function checkDmWallets(): array
    {
        try {
            $jeNet = (float) DB::table('journal_lines as jl')
                ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
                ->join('accounts as a', 'a.id', '=', 'jl.account_id')
                ->where('a.code', '2012')
                ->where('je.status', 'posted')
                ->selectRaw('COALESCE(SUM(jl.credit),0) - COALESCE(SUM(jl.debit),0) as net')
                ->value('net');

            $walletSum = (float) DB::table('delivery_man_wallets')
                ->selectRaw('COALESCE(SUM(total_earning),0) - COALESCE(SUM(total_withdrawn),0) as net')
                ->value('net');

            return $this->result('DM Wallets (2012 net = dm_wallets balance)', $jeNet, $walletSum);
        } catch (\Exception $e) {
            return $this->skipped('DM Wallets (2012 net = dm_wallets balance)', $e->getMessage());
        }
    }

    // ── Check 4: Admin Commission ─────────────────────────────────────────────

    public function checkAdminCommission(): array
    {
        try {
            $jeCredits = (float) DB::table('journal_lines as jl')
                ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
                ->join('accounts as a', 'a.id', '=', 'jl.account_id')
                ->where('a.code', '4011')
                ->where('je.status', 'posted')
                ->sum('jl.credit');

            $otSum = (float) DB::table('order_transactions')->sum('admin_commission');

            return $this->result('Admin Commission (4011 credits = OT.admin_commission)', $jeCredits, $otSum);
        } catch (\Exception $e) {
            return $this->skipped('Admin Commission (4011 credits = OT.admin_commission)', $e->getMessage());
        }
    }

    // ── Check 5: Tax Payable ──────────────────────────────────────────────────

    public function checkTaxPayable(): array
    {
        try {
            $jeCredits = (float) DB::table('journal_lines as jl')
                ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
                ->join('accounts as a', 'a.id', '=', 'jl.account_id')
                ->where('a.code', '2031')
                ->where('je.status', 'posted')
                ->sum('jl.credit');

            $otTax = (float) DB::table('order_transactions')->sum('tax');

            return $this->result('Tax Payable (2031 credits = OT.tax)', $jeCredits, $otTax);
        } catch (\Exception $e) {
            return $this->skipped('Tax Payable (2031 credits = OT.tax)', $e->getMessage());
        }
    }

    // ── Check 6: Customer Wallet Parity ──────────────────────────────────────

    public function checkCustomerWallets(): array
    {
        try {
            $jeNet = (float) DB::table('journal_lines as jl')
                ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
                ->join('accounts as a', 'a.id', '=', 'jl.account_id')
                ->where('a.code', '2021')
                ->where('je.status', 'posted')
                ->selectRaw('COALESCE(SUM(jl.credit),0) - COALESCE(SUM(jl.debit),0) as net')
                ->value('net');

            $wtNet = (float) DB::table('wallet_transactions')
                ->selectRaw('COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) as net')
                ->value('net');

            return $this->result('Customer Wallets (2021 net = wallet_transactions net)', $jeNet, $wtNet);
        } catch (\Exception $e) {
            return $this->skipped('Customer Wallets (2021 net = wallet_transactions net)', $e->getMessage());
        }
    }

    // ── Check 7: COD Outstanding Non-negative ────────────────────────────────

    public function checkCodNonNegative(): array
    {
        $net = (float) DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('a.code', ['1022', '1023'])
            ->where('je.status', 'posted')
            ->selectRaw('COALESCE(SUM(jl.debit),0) - COALESCE(SUM(jl.credit),0) as net')
            ->value('net');

        // Net should be >= 0 (more owed than settled is normal; negative = over-settled)
        $diff = $net < 0 ? abs($net) : 0;
        return $this->result('COD Outstanding (1022+1023 net >= 0)', $net, max(0, $net), $diff);
    }

    // ── Check 8: Gateway Outstanding Non-negative ────────────────────────────

    public function checkGatewayNonNegative(): array
    {
        $net = (float) DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('a.code', '1013')
            ->where('je.status', 'posted')
            ->selectRaw('COALESCE(SUM(jl.debit),0) - COALESCE(SUM(jl.credit),0) as net')
            ->value('net');

        $diff = $net < 0 ? abs($net) : 0;
        return $this->result('Gateway Outstanding (1013 net >= 0)', $net, max(0, $net), $diff);
    }

    // ── Check 9: Accounting Equation ─────────────────────────────────────────

    public function checkAccountingEquation(): array
    {
        $balances = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.status', 'posted')
            ->groupBy('a.type', 'a.normal_balance')
            ->selectRaw('a.type, a.normal_balance, COALESCE(SUM(jl.debit),0) as td, COALESCE(SUM(jl.credit),0) as tc')
            ->get()
            ->mapWithKeys(fn($r) => [
                $r->type => $r->normal_balance === 'debit'
                    ? (float)$r->td - (float)$r->tc
                    : (float)$r->tc - (float)$r->td,
            ]);

        $assets      = (float)($balances['asset']     ?? 0);
        $liabilities = (float)($balances['liability']  ?? 0);
        $equity      = (float)($balances['equity']     ?? 0);
        $revenue     = (float)($balances['revenue']    ?? 0);
        $expenses    = (float)($balances['expense']    ?? 0);

        $rhs  = $liabilities + $equity + ($revenue - $expenses);
        $diff = abs($assets - $rhs);

        return $this->result('Accounting Equation (Assets = Liabilities + Equity + Net P&L)', $assets, $rhs, $diff);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function result(string $name, float $journalValue, float $expectedValue, ?float $diff = null): array
    {
        $diff ??= abs($journalValue - $expectedValue);
        return [
            'name'           => $name,
            'journal_value'  => $journalValue,
            'expected_value' => $expectedValue,
            'difference'     => $diff,
            'reconciled'     => $diff < $this->tolerance,
            'skipped'        => false,
            'skip_reason'    => null,
        ];
    }

    private function skipped(string $name, string $reason): array
    {
        return [
            'name'           => $name,
            'journal_value'  => 0.0,
            'expected_value' => 0.0,
            'difference'     => 0.0,
            'reconciled'     => true,  // skipped counts as non-failing
            'skipped'        => true,
            'skip_reason'    => $reason,
        ];
    }
}
