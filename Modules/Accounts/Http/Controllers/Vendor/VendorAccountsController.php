<?php

namespace Modules\Accounts\Http\Controllers\Vendor;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounts\Exports\AccountingExport;
use Modules\Accounts\Services\AccountingService;
use App\CentralLogics\Helpers;

class VendorAccountsController extends Controller
{
    public function __construct(private readonly AccountingService $svc) {}

    /**
     * Account overview: summary cards + recent journal entries for this store.
     */
    private function currentStore(): \App\Models\Store
    {
        return \App\Models\Store::find(Helpers::get_store_id());
    }

    public function index(Request $request)
    {
        $store   = $this->currentStore();
        $storeId = $store->id;
        $from    = $request->get('from', now()->startOfMonth()->toDateString());
        $to      = $request->get('to',   now()->toDateString());

        // Current period statement (last 10 rows for the overview)
        $statement = $this->svc->storeStatement($storeId, $from, $to);

        // Quick P&L scoped to this store — revenue/expenses where store_id matches
        $recentEntries = \Modules\Accounts\Entities\JournalEntry::with('lines.account')
            ->where(function ($q) use ($storeId) {
                $q->whereHas('lines', fn($lq) => $lq->where('store_id', $storeId));
            })
            ->where('status', 'posted')
            ->latest('posted_at')
            ->limit(10)
            ->get();

        return view('accounts::vendor.index', compact('store', 'storeId', 'from', 'to', 'statement', 'recentEntries'));
    }

    /**
     * Full account statement for this store.
     */
    public function statement(Request $request)
    {
        $store   = $this->currentStore();
        $storeId = $store->id;
        $from    = $request->get('from', now()->startOfMonth()->toDateString());
        $to      = $request->get('to',   now()->toDateString());

        $statement = $this->svc->storeStatement($storeId, $from, $to);

        if ($request->get('format') === 'excel') {
            $rows = $statement['rows']->map(fn($r) => [
                \Carbon\Carbon::parse($r->posted_at)->format('Y-m-d'),
                $r->entry_number,
                str_replace('_', ' ', $r->event_type),
                $r->reference_type ? "{$r->reference_type} #{$r->reference_id}" : '',
                $r->description ?? '',
                $r->debit  > 0 ? number_format($r->debit,  2) : '',
                $r->credit > 0 ? number_format($r->credit, 2) : '',
                number_format($r->running_balance, 2),
            ]);
            $rows->prepend(['Opening Balance', '', '', '', '', '', '', number_format($statement['opening_balance'], 2)]);
            $rows->push(['Closing Balance', '', '', '', '', '', '', number_format($statement['closing_balance'], 2)]);
            return Excel::download(
                new AccountingExport(collect($rows), ['Date', 'Entry #', 'Event', 'Reference', 'Description', 'Debit', 'Credit', 'Balance']),
                "store-statement-{$storeId}-{$from}-{$to}.xlsx"
            );
        }

        return view('accounts::vendor.statement', compact('store', 'storeId', 'from', 'to', 'statement'));
    }

    /**
     * Earnings report (P&L filtered to this store's revenue/expense accounts).
     */
    public function earnings(Request $request)
    {
        $store   = $this->currentStore();
        $storeId = $store->id;
        $from    = $request->get('from', now()->startOfMonth()->toDateString());
        $to      = $request->get('to',   now()->toDateString());

        // Store-level P&L: only lines belonging to this store
        $rows = \Illuminate\Support\Facades\DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('a.type', ['revenue', 'expense'])
            ->where('jl.store_id', $storeId)
            ->where('je.status', 'posted')
            ->whereBetween('je.posted_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.sort_order')
            ->orderBy('a.sort_order')->orderBy('a.code')
            ->selectRaw('a.code, a.name, a.type, COALESCE(SUM(jl.debit),0) as td, COALESCE(SUM(jl.credit),0) as tc')
            ->get()
            ->map(fn($r) => (object)[
                'account_code' => $r->code,
                'account_name' => $r->name,
                'type'         => $r->type,
                'amount'       => $r->type === 'revenue'
                    ? (float)$r->tc - (float)$r->td
                    : (float)$r->td - (float)$r->tc,
            ]);

        $revenueRows   = $rows->where('type', 'revenue')->values();
        $expenseRows   = $rows->where('type', 'expense')->values();
        $totalRevenue  = (float)$revenueRows->sum('amount');
        $totalExpenses = (float)$expenseRows->sum('amount');
        $netEarnings   = $totalRevenue - $totalExpenses;

        return view('accounts::vendor.earnings', compact(
            'store', 'storeId', 'from', 'to',
            'revenueRows', 'expenseRows', 'totalRevenue', 'totalExpenses', 'netEarnings'
        ));
    }
}
