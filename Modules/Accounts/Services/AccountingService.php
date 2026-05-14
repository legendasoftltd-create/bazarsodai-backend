<?php

namespace Modules\Accounts\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\AccountingRule;
use Modules\Accounts\Entities\JournalEntry;
use Modules\Accounts\Entities\JournalLine;
use Modules\Accounts\Exceptions\UnbalancedJournalException;

class AccountingService
{
    /**
     * Post a journal entry for the given event type.
     *
     * @param  string  $eventType   Matches accounting_rules.event_type
     * @param  array   $data        Amount fields keyed by amount_field name
     * @param  array   $context     Optional: reference_type, reference_id, description,
     *                              store_id, delivery_man_id, order_id, user_id
     * @return JournalEntry
     *
     * @throws UnbalancedJournalException
     * @throws \RuntimeException if no active rule found for event_type
     */
    public function post(string $eventType, array $data, array $context = []): JournalEntry
    {
        $rule = AccountingRule::active()->where('event_type', $eventType)->firstOrFail();

        // Resolve account IDs once so we don't hit DB per line
        $codes     = array_column($rule->lines, 'account_code');
        $accountMap = Account::whereIn('code', $codes)->pluck('id', 'code');

        // Build debit/credit amounts from the rule lines + incoming $data
        $lines      = [];
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($rule->lines as $ruleLine) {
            $amount = $this->resolveAmount($ruleLine, $data);

            if ($amount <= 0) {
                continue; // skip zero-value lines (e.g. no tax on some orders)
            }

            $accountId = $accountMap[$ruleLine['account_code']] ?? null;
            if (!$accountId) {
                continue; // account code missing from CoA — skip gracefully
            }

            $isDebit = $ruleLine['side'] === 'debit';

            $lines[] = [
                'account_id'       => $accountId,
                'debit'            => $isDebit  ? $amount : 0,
                'credit'           => !$isDebit ? $amount : 0,
                'description'      => $context['description'] ?? null,
                'store_id'         => $context['store_id']         ?? null,
                'delivery_man_id'  => $context['delivery_man_id']  ?? null,
                'order_id'         => $context['order_id']         ?? null,
                'user_id'          => $context['user_id']          ?? null,
                'meta'             => isset($context['meta']) ? json_encode($context['meta']) : null,
            ];

            if ($isDebit) {
                $debitTotal += $amount;
            } else {
                $creditTotal += $amount;
            }
        }

        if (abs($debitTotal - $creditTotal) > 0.001) {
            throw new UnbalancedJournalException($debitTotal, $creditTotal, $eventType);
        }

        return DB::transaction(function () use ($eventType, $lines, $context, $debitTotal) {
            $entry = JournalEntry::create([
                'entry_number'   => $this->nextEntryNumber(),
                'reference_type' => $context['reference_type'] ?? null,
                'reference_id'   => $context['reference_id']   ?? null,
                'event_type'     => $eventType,
                'description'    => $context['description']    ?? null,
                'status'         => 'posted',
                'created_by'     => $context['created_by']     ?? null,
                'posted_at'      => now(),
            ]);

            foreach ($lines as $line) {
                $line['journal_entry_id'] = $entry->id;
                JournalLine::create($line);
            }

            return $entry;
        });
    }

    /**
     * Create a reversing journal entry that mirrors every line of the original.
     * The new entry's reversal_of_id points back to the original.
     *
     * @throws UnbalancedJournalException  (shouldn't happen if original was balanced)
     */
    public function reverse(JournalEntry $original, string $description = ''): JournalEntry
    {
        $original->loadMissing('lines');

        return DB::transaction(function () use ($original, $description) {
            $reversal = JournalEntry::create([
                'entry_number'   => $this->nextEntryNumber(),
                'reference_type' => $original->reference_type,
                'reference_id'   => $original->reference_id,
                'event_type'     => $original->event_type . '_reversal',
                'description'    => $description ?: "Reversal of {$original->entry_number}",
                'status'         => 'reversed',
                'reversal_of_id' => $original->id,
                'posted_at'      => now(),
            ]);

            foreach ($original->lines as $originalLine) {
                JournalLine::create([
                    'journal_entry_id' => $reversal->id,
                    'account_id'       => $originalLine->account_id,
                    // Swap debit ↔ credit
                    'debit'            => $originalLine->credit,
                    'credit'           => $originalLine->debit,
                    'description'      => $reversal->description,
                    'store_id'         => $originalLine->store_id,
                    'delivery_man_id'  => $originalLine->delivery_man_id,
                    'order_id'         => $originalLine->order_id,
                    'user_id'          => $originalLine->user_id,
                    'meta'             => $originalLine->meta ? json_encode($originalLine->meta) : null,
                ]);
            }

            // Mark original as reversed
            $original->update(['status' => 'reversed']);

            return $reversal;
        });
    }

    /**
     * Post a journal entry from pre-built lines (no rules lookup needed).
     *
     * Each line: ['account_code' => '1013', 'side' => 'debit'|'credit', 'amount' => 500.00]
     *
     * Use this when the calling code already knows the exact amounts
     * (e.g. OrderLogic, where amounts are computed before reaching here).
     *
     * @throws UnbalancedJournalException
     */
    public function postDirect(string $eventType, array $rawLines, array $context = []): JournalEntry
    {
        $codes      = array_column($rawLines, 'account_code');
        $accountMap = Account::whereIn('code', array_unique($codes))->pluck('id', 'code');

        $lines       = [];
        $debitTotal  = 0.0;
        $creditTotal = 0.0;

        foreach ($rawLines as $raw) {
            $amount = (float)($raw['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $accountId = $accountMap[$raw['account_code']] ?? null;
            if (!$accountId) {
                continue;
            }

            $isDebit = $raw['side'] === 'debit';

            $lines[] = [
                'account_id'      => $accountId,
                'debit'           => $isDebit  ? $amount : 0,
                'credit'          => !$isDebit ? $amount : 0,
                'description'     => $context['description']     ?? null,
                'store_id'        => $context['store_id']        ?? null,
                'delivery_man_id' => $context['delivery_man_id'] ?? null,
                'order_id'        => $context['order_id']        ?? null,
                'user_id'         => $context['user_id']         ?? null,
                'meta'            => isset($context['meta']) ? json_encode($context['meta']) : null,
            ];

            if ($isDebit) {
                $debitTotal += $amount;
            } else {
                $creditTotal += $amount;
            }
        }

        if (abs($debitTotal - $creditTotal) > 0.001) {
            throw new UnbalancedJournalException($debitTotal, $creditTotal, $eventType);
        }

        return DB::transaction(function () use ($eventType, $lines, $context) {
            $entry = JournalEntry::create([
                'entry_number'   => $this->nextEntryNumber(),
                'reference_type' => $context['reference_type'] ?? null,
                'reference_id'   => $context['reference_id']   ?? null,
                'event_type'     => $eventType,
                'description'    => $context['description']    ?? null,
                'status'         => 'posted',
                'created_by'     => $context['created_by']     ?? null,
                'posted_at'      => now(),
            ]);

            foreach ($lines as $line) {
                $line['journal_entry_id'] = $entry->id;
                JournalLine::create($line);
            }

            return $entry;
        });
    }

    // ── Reports ──────────────────────────────────────────────────────────────

    /**
     * Store statement: movements on account 2011 (Store Wallet Payable) for one store.
     *
     * CR = earnings credited to store, DR = disbursements paid out.
     * Opening/closing balance = what the platform owes the store.
     *
     * @return array{store_id:int, account:Account, opening_balance:float, rows:\Illuminate\Support\Collection, closing_balance:float}
     */
    public function storeStatement(int $storeId, string $from, string $to): array
    {
        return $this->partyStatement('2011', 'store_id', $storeId, $from, $to);
    }

    /**
     * Delivery-man statement: movements on account 2012 (DM Wallet Payable) for one DM.
     *
     * CR = earnings credited to DM, DR = disbursements paid out.
     *
     * @return array{party_id:int, account:Account, opening_balance:float, rows:\Illuminate\Support\Collection, closing_balance:float}
     */
    public function dmStatement(int $dmId, string $from, string $to): array
    {
        return $this->partyStatement('2012', 'delivery_man_id', $dmId, $from, $to);
    }

    private function partyStatement(string $accountCode, string $dimensionColumn, int $partyId, string $from, string $to): array
    {
        $account = Account::where('code', $accountCode)->firstOrFail();

        // Opening balance: cumulative before $from (credit-normal account)
        $pre = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $account->id)
            ->where("jl.{$dimensionColumn}", $partyId)
            ->where('je.status', 'posted')
            ->where('je.posted_at', '<', $from . ' 00:00:00')
            ->selectRaw('COALESCE(SUM(jl.credit), 0) as c, COALESCE(SUM(jl.debit), 0) as d')
            ->first();

        $openingBalance = (float)$pre->c - (float)$pre->d;

        // Lines in period
        $lines = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $account->id)
            ->where("jl.{$dimensionColumn}", $partyId)
            ->where('je.status', 'posted')
            ->whereBetween('je.posted_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderBy('je.posted_at')
            ->orderBy('je.id')
            ->selectRaw('je.posted_at, je.entry_number, je.event_type, je.reference_type, je.reference_id, je.description, jl.debit, jl.credit')
            ->get();

        $running = $openingBalance;
        $rows = $lines->map(function ($row) use (&$running) {
            $running += (float)$row->credit - (float)$row->debit;
            $row->running_balance = $running;
            return $row;
        });

        return [
            'party_id'        => $partyId,
            'account'         => $account,
            'opening_balance' => $openingBalance,
            'rows'            => $rows,
            'closing_balance' => $running,
        ];
    }

    /**
     * Profit & Loss for [from, to].
     *
     * Revenue accounts (type=revenue, credit-normal) and Expense accounts (type=expense, debit-normal).
     * Skips accounts with zero net movement in the period.
     *
     * @return array{revenue_rows:\Illuminate\Support\Collection, expense_rows:\Illuminate\Support\Collection, total_revenue:float, total_expenses:float, net_profit:float}
     */
    public function profitAndLoss(string $from, string $to, ?int $moduleId = null): array
    {
        $storeIds = $this->storeIdsForModule($moduleId);

        $rows = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('a.type', ['revenue', 'expense'])
            ->where('je.status', 'posted')
            ->whereBetween('je.posted_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($storeIds !== null, fn($q) => $this->applyModuleFilter($q, $storeIds))
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.sort_order')
            ->orderBy('a.sort_order')->orderBy('a.code')
            ->selectRaw('a.code, a.name, a.type, COALESCE(SUM(jl.debit),0) as td, COALESCE(SUM(jl.credit),0) as tc')
            ->get()
            ->map(fn($r) => (object)[
                'account_code' => $r->code,
                'account_name' => $r->name,
                'type'         => $r->type,
                'amount'       => $r->type === 'revenue'
                    ? (float)$r->tc - (float)$r->td   // credit-normal
                    : (float)$r->td - (float)$r->tc,  // debit-normal
            ]);

        $revenueRows  = $rows->where('type', 'revenue');
        $expenseRows  = $rows->where('type', 'expense');
        $totalRevenue = (float)$revenueRows->sum('amount');
        $totalExpenses = (float)$expenseRows->sum('amount');

        return [
            'revenue_rows'  => $revenueRows->values(),
            'expense_rows'  => $expenseRows->values(),
            'total_revenue' => $totalRevenue,
            'total_expenses'=> $totalExpenses,
            'net_profit'    => $totalRevenue - $totalExpenses,
        ];
    }

    /**
     * Balance sheet as at $date (cumulative from beginning of time).
     *
     * Revenue / Expense accounts are folded into equity as "Current Period Net".
     *
     * @return array{asset_rows:\Illuminate\Support\Collection, liability_rows:\Illuminate\Support\Collection, equity_rows:\Illuminate\Support\Collection, total_assets:float, total_liabilities:float, total_equity:float, net_profit:float, balanced:bool}
     */
    public function balanceSheet(string $date, ?int $moduleId = null): array
    {
        $storeIds = $this->storeIdsForModule($moduleId);

        $rows = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.status', 'posted')
            ->where('je.posted_at', '<=', $date . ' 23:59:59')
            ->when($storeIds !== null, fn($q) => $this->applyModuleFilter($q, $storeIds))
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.normal_balance', 'a.sort_order')
            ->orderBy('a.sort_order')->orderBy('a.code')
            ->selectRaw('a.code, a.name, a.type, a.normal_balance, COALESCE(SUM(jl.debit),0) as td, COALESCE(SUM(jl.credit),0) as tc')
            ->get()
            ->map(fn($r) => (object)[
                'account_code'   => $r->code,
                'account_name'   => $r->name,
                'type'           => $r->type,
                'normal_balance' => $r->normal_balance,
                'balance'        => $r->normal_balance === 'debit'
                    ? (float)$r->td - (float)$r->tc
                    : (float)$r->tc - (float)$r->td,
            ]);

        $assetRows     = $rows->whereIn('type', ['asset'])->where('balance', '!=', 0)->values();
        $liabilityRows = $rows->whereIn('type', ['liability'])->where('balance', '!=', 0)->values();
        $equityRows    = $rows->whereIn('type', ['equity'])->where('balance', '!=', 0)->values();

        // P&L accounts fold into net profit (not shown as individual balance-sheet lines)
        $revenueNet  = (float)$rows->where('type', 'revenue')->sum('balance');
        $expenseNet  = (float)$rows->where('type', 'expense')->sum('balance');
        $netProfit   = $revenueNet - $expenseNet;

        $totalAssets      = (float)$assetRows->sum('balance');
        $totalLiabilities = (float)$liabilityRows->sum('balance');
        $totalEquity      = (float)$equityRows->sum('balance') + $netProfit;

        return [
            'asset_rows'       => $assetRows,
            'liability_rows'   => $liabilityRows,
            'equity_rows'      => $equityRows,
            'total_assets'     => $totalAssets,
            'total_liabilities'=> $totalLiabilities,
            'total_equity'     => $totalEquity,
            'net_profit'       => $netProfit,
            'balanced'         => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Tax report: all movements on account 2031 (VAT / Tax Collected Payable) in [from, to].
     *
     * @return array{rows:\Illuminate\Support\Collection, total_collected:float, total_remitted:float, net_payable:float}
     */
    public function taxReport(string $from, string $to, ?int $moduleId = null): array
    {
        $storeIds = $this->storeIdsForModule($moduleId);

        $rows = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('a.code', '2031')
            ->where('je.status', 'posted')
            ->whereBetween('je.posted_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($storeIds !== null, fn($q) => $this->applyModuleFilter($q, $storeIds))
            ->orderBy('je.posted_at')->orderBy('je.id')
            ->selectRaw('je.posted_at, je.entry_number, je.event_type, je.reference_type, je.reference_id, je.description, jl.debit, jl.credit')
            ->get();

        return [
            'rows'            => $rows,
            'total_collected' => (float)$rows->sum('credit'),
            'total_remitted'  => (float)$rows->sum('debit'),
            'net_payable'     => (float)$rows->sum('credit') - (float)$rows->sum('debit'),
        ];
    }

    /**
     * COD reconciliation: movements on accounts 1022 (DM) and 1023 (Store) in [from, to].
     *
     * DR = COD orders placed (cash owed), CR = COD cash collected/settled.
     * Outstanding = net DR balance.
     *
     * @return array{rows:\Illuminate\Support\Collection, total_owed:float, total_settled:float, outstanding:float}
     */
    public function codReconciliation(string $from, string $to, ?int $moduleId = null): array
    {
        $storeIds = $this->storeIdsForModule($moduleId);

        $rows = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('a.code', ['1022', '1023'])
            ->where('je.status', 'posted')
            ->whereBetween('je.posted_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($storeIds !== null, fn($q) => $this->applyModuleFilter($q, $storeIds))
            ->orderBy('je.posted_at')->orderBy('je.id')
            ->selectRaw('je.posted_at, je.entry_number, je.event_type, je.reference_type, je.reference_id, je.description, a.code as account_code, jl.debit, jl.credit')
            ->get();

        return [
            'rows'          => $rows,
            'total_owed'    => (float)$rows->sum('debit'),
            'total_settled' => (float)$rows->sum('credit'),
            'outstanding'   => (float)$rows->sum('debit') - (float)$rows->sum('credit'),
        ];
    }

    /**
     * Gateway reconciliation: movements on account 1013 (and optionally 1014) in [from, to].
     *
     * DR = gateway payments received, CR = settlements to bank.
     * Outstanding = net DR balance (cash yet to be swept to bank).
     *
     * @param  string|null  $accountCode  '1013' (default) or '1014' for bKash clearing
     * @return array{rows:\Illuminate\Support\Collection, total_in:float, total_out:float, outstanding:float, account_code:string}
     */
    public function gatewayReconciliation(string $from, string $to, ?string $accountCode = '1013', ?int $moduleId = null): array
    {
        $accountCode = $accountCode ?: '1013';
        $storeIds    = $this->storeIdsForModule($moduleId);

        $rows = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('a.code', $accountCode)
            ->where('je.status', 'posted')
            ->whereBetween('je.posted_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($storeIds !== null, fn($q) => $this->applyModuleFilter($q, $storeIds))
            ->orderBy('je.posted_at')->orderBy('je.id')
            ->selectRaw('je.posted_at, je.entry_number, je.event_type, je.reference_type, je.reference_id, je.description, jl.debit, jl.credit')
            ->get();

        return [
            'rows'         => $rows,
            'total_in'     => (float)$rows->sum('debit'),
            'total_out'    => (float)$rows->sum('credit'),
            'outstanding'  => (float)$rows->sum('debit') - (float)$rows->sum('credit'),
            'account_code' => $accountCode,
        ];
    }

    // ── Module-scoped report helpers ──────────────────────────────────────────

    /**
     * Resolve an array of store_ids that belong to a given module.
     * Returns null when no module filter is requested (meaning "all stores").
     *
     * @return int[]|null
     */
    private function storeIdsForModule(?int $moduleId): ?array
    {
        if (!$moduleId) {
            return null;
        }
        return DB::table('stores')
            ->where('module_id', $moduleId)
            ->pluck('id')
            ->map(fn($id) => (int)$id)
            ->all();
    }

    /**
     * Apply a store_id filter to a query builder when module scoping is active.
     * Lines with no store_id (wallet topups, subscriptions, etc.) are always included.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int[]|null $storeIds  null = no filter
     */
    private function applyModuleFilter($query, ?array $storeIds): void
    {
        if ($storeIds === null) {
            return;
        }
        if (empty($storeIds)) {
            $query->whereRaw('1 = 0'); // module has no stores — return nothing
        } else {
            $query->where(function ($q) use ($storeIds) {
                $q->whereNull('jl.store_id')
                  ->orWhereIn('jl.store_id', $storeIds);
            });
        }
    }

    /**
     * Trial balance: one row per account that had activity in [from, to].
     *
     * @return array{rows: \Illuminate\Support\Collection, total_debit: float, total_credit: float, balanced: bool}
     */
    public function trialBalance(string $from, string $to, ?int $moduleId = null): array
    {
        $storeIds = $this->storeIdsForModule($moduleId);

        $rows = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.status', 'posted')
            ->whereBetween('je.posted_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($storeIds !== null, fn($q) => $this->applyModuleFilter($q, $storeIds))
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.normal_balance', 'a.sort_order')
            ->orderBy('a.sort_order')
            ->orderBy('a.code')
            ->selectRaw('
                a.code          as account_code,
                a.name          as account_name,
                a.type,
                a.normal_balance,
                COALESCE(SUM(jl.debit),  0) as total_debit,
                COALESCE(SUM(jl.credit), 0) as total_credit
            ')
            ->get()
            ->map(function ($row) {
                $row->balance = $row->normal_balance === 'debit'
                    ? (float)$row->total_debit - (float)$row->total_credit
                    : (float)$row->total_credit - (float)$row->total_debit;
                return $row;
            });

        $totalDebit  = (float)$rows->sum('total_debit');
        $totalCredit = (float)$rows->sum('total_credit');

        return [
            'rows'         => $rows,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'balanced'     => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    /**
     * General ledger for a single account in [from, to] with running balance.
     *
     * @return array{account: Account, opening_balance: float, rows: \Illuminate\Support\Collection, closing_balance: float}
     */
    public function ledger(int $accountId, string $from, string $to, ?int $moduleId = null): array
    {
        $account  = Account::findOrFail($accountId);
        $storeIds = $this->storeIdsForModule($moduleId);

        // Opening balance: all activity strictly before $from
        $openingRaw = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $accountId)
            ->where('je.status', 'posted')
            ->where('je.posted_at', '<', $from . ' 00:00:00')
            ->when($storeIds !== null, fn($q) => $this->applyModuleFilter($q, $storeIds))
            ->selectRaw('COALESCE(SUM(jl.debit), 0) as d, COALESCE(SUM(jl.credit), 0) as c')
            ->first();

        $openingBalance = $account->normal_balance === 'debit'
            ? (float)$openingRaw->d - (float)$openingRaw->c
            : (float)$openingRaw->c - (float)$openingRaw->d;

        // Lines within period
        $lines = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $accountId)
            ->where('je.status', 'posted')
            ->whereBetween('je.posted_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($storeIds !== null, fn($q) => $this->applyModuleFilter($q, $storeIds))
            ->orderBy('je.posted_at')
            ->orderBy('je.id')
            ->selectRaw('
                je.posted_at,
                je.entry_number,
                je.event_type,
                je.reference_type,
                je.reference_id,
                je.description,
                jl.debit,
                jl.credit
            ')
            ->get();

        // Compute running balance
        $running = $openingBalance;
        $rows = $lines->map(function ($row) use (&$running, $account) {
            $movement = $account->normal_balance === 'debit'
                ? (float)$row->debit - (float)$row->credit
                : (float)$row->credit - (float)$row->debit;

            $running += $movement;
            $row->running_balance = $running;
            return $row;
        });

        return [
            'account'         => $account,
            'opening_balance' => $openingBalance,
            'rows'            => $rows,
            'closing_balance' => $running,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveAmount(array $ruleLine, array $data): float
    {
        if (isset($ruleLine['fixed_amount'])) {
            return (float)$ruleLine['fixed_amount'];
        }

        $field = $ruleLine['amount_field'] ?? null;

        return $field ? (float)($data[$field] ?? 0) : 0.0;
    }

    private function nextEntryNumber(): string
    {
        $prefix  = config('accounts.entry_number_prefix', 'JE');
        $padding = (int)config('accounts.entry_number_padding', 6);

        $last = JournalEntry::lockForUpdate()->max('id') ?? 0;

        return $prefix . '-' . str_pad($last + 1, $padding, '0', STR_PAD_LEFT);
    }
}
