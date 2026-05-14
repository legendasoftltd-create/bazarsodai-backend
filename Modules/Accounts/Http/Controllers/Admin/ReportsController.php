<?php

namespace Modules\Accounts\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Exports\AccountingExport;
use Modules\Accounts\Services\AccountingService;
use Illuminate\Support\Collection;

class ReportsController extends Controller
{
    public function __construct(private readonly AccountingService $svc) {}

    /** Returns the active module ID from config, or null for the central (all-modules) view. */
    private function currentModuleId(Request $request): ?int
    {
        $id = $request->get('module_id') ?? config('module.current_module_id');
        return $id ? (int)$id : null;
    }

    /**
     * Returns a $scope array for views:
     *   ['type' => 'central', 'label' => 'All Modules', 'module' => null]
     *   ['type' => 'module',  'label' => 'Food',        'module' => Module]
     */
    private function scopeInfo(?int $moduleId): array
    {
        if (!$moduleId) {
            return ['type' => 'central', 'label' => 'All Modules', 'module' => null];
        }

        $module = \App\Models\Module::find($moduleId);
        return [
            'type'   => 'module',
            'label'  => $module?->module_name ?? "Module #{$moduleId}",
            'module' => $module,
        ];
    }

    // ── 7.01  Trial Balance ───────────────────────────────────────────────────

    public function trialBalance(Request $request)
    {
        $from     = $request->get('from', now()->startOfMonth()->toDateString());
        $to       = $request->get('to',   now()->toDateString());
        $moduleId = $this->currentModuleId($request);

        $data  = $this->svc->trialBalance($from, $to, $moduleId);
        $scope = $this->scopeInfo($moduleId);

        if ($request->get('format') === 'excel') {
            $rows = $data['rows']->map(fn($r) => [
                $r->account_code, $r->account_name, ucfirst($r->type),
                number_format($r->total_debit, 2), number_format($r->total_credit, 2), number_format($r->balance, 2),
            ]);
            $rows->push(['', '', 'TOTAL', number_format($data['total_debit'], 2), number_format($data['total_credit'], 2), '']);
            return Excel::download(
                new AccountingExport(collect($rows), ['Code', 'Account', 'Type', 'Debit', 'Credit', 'Balance']),
                "trial-balance-{$from}-{$to}.xlsx"
            );
        }

        if ($request->get('format') === 'pdf') {
            return $this->pdf('accounts::admin.reports.trial-balance', array_merge($data, compact('from', 'to')), "trial-balance-{$from}-{$to}.pdf");
        }

        return view('accounts::admin.reports.trial-balance', array_merge($data, compact('from', 'to', 'scope')));
    }

    // ── 7.02  General Ledger ──────────────────────────────────────────────────

    public function generalLedger(Request $request)
    {
        $from      = $request->get('from', now()->startOfMonth()->toDateString());
        $to        = $request->get('to',   now()->toDateString());
        $accountId = $request->get('account_id');

        $moduleId = $this->currentModuleId($request);
        $scope    = $this->scopeInfo($moduleId);
        $accounts = Account::active()->orderBy('sort_order')->orderBy('code')->get();
        $ledger   = $accountId ? $this->svc->ledger((int)$accountId, $from, $to, $moduleId) : null;

        if ($ledger && $request->get('format') === 'excel') {
            $rows = $ledger['rows']->map(fn($r) => [
                \Carbon\Carbon::parse($r->posted_at)->format('Y-m-d'),
                $r->entry_number, $r->event_type,
                $r->reference_type ? "{$r->reference_type} #{$r->reference_id}" : '',
                $r->description ?? '',
                $r->debit  > 0 ? number_format($r->debit,  2) : '',
                $r->credit > 0 ? number_format($r->credit, 2) : '',
                number_format($r->running_balance, 2),
            ]);
            return Excel::download(
                new AccountingExport(collect($rows), ['Date', 'Entry #', 'Event', 'Reference', 'Description', 'Debit', 'Credit', 'Balance']),
                "general-ledger-{$ledger['account']->code}-{$from}-{$to}.xlsx"
            );
        }

        if ($ledger && $request->get('format') === 'pdf') {
            return $this->pdf('accounts::admin.reports.general-ledger', compact('accounts', 'accountId', 'from', 'to', 'ledger'), "general-ledger-{$from}-{$to}.pdf");
        }

        return view('accounts::admin.reports.general-ledger', compact('accounts', 'accountId', 'from', 'to', 'ledger', 'scope'));
    }

    // ── 9.01  Store Statement ─────────────────────────────────────────────────

    public function storeStatement(Request $request)
    {
        $from    = $request->get('from', now()->startOfMonth()->toDateString());
        $to      = $request->get('to',   now()->toDateString());
        $storeId = $request->get('store_id');

        $moduleId  = $this->currentModuleId($request);
        $scope     = $this->scopeInfo($moduleId);
        // When in a module context, only show stores belonging to that module
        $stores    = \App\Models\Store::select('id', 'name')
            ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
            ->orderBy('name')->get();
        $statement = $storeId ? $this->svc->storeStatement((int)$storeId, $from, $to) : null;

        if ($statement && $request->get('format') === 'excel') {
            return $this->exportStatement($statement, "store-statement-{$storeId}-{$from}-{$to}.xlsx");
        }

        if ($statement && $request->get('format') === 'pdf') {
            return $this->pdf(
                'accounts::admin.reports.store-statement',
                compact('stores', 'storeId', 'from', 'to', 'statement'),
                "store-statement-{$storeId}-{$from}-{$to}.pdf"
            );
        }

        return view('accounts::admin.reports.store-statement', compact('stores', 'storeId', 'from', 'to', 'statement', 'scope'));
    }

    // ── 9.02  DM Statement ────────────────────────────────────────────────────

    public function dmStatement(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to',   now()->toDateString());
        $dmId = $request->get('dm_id');

        $moduleId    = $this->currentModuleId($request);
        $scope       = $this->scopeInfo($moduleId);
        $deliveryMen = \App\Models\DeliveryMan::select('id', 'f_name', 'l_name')->orderBy('f_name')->get();
        $statement   = $dmId ? $this->svc->dmStatement((int)$dmId, $from, $to) : null;

        if ($statement && $request->get('format') === 'excel') {
            return $this->exportStatement($statement, "dm-statement-{$dmId}-{$from}-{$to}.xlsx");
        }

        if ($statement && $request->get('format') === 'pdf') {
            return $this->pdf(
                'accounts::admin.reports.dm-statement',
                compact('deliveryMen', 'dmId', 'from', 'to', 'statement'),
                "dm-statement-{$dmId}-{$from}-{$to}.pdf"
            );
        }

        return view('accounts::admin.reports.dm-statement', compact('deliveryMen', 'dmId', 'from', 'to', 'statement', 'scope'));
    }

    // ── 8.01  Profit & Loss ───────────────────────────────────────────────────

    public function profitAndLoss(Request $request)
    {
        $from     = $request->get('from', now()->startOfMonth()->toDateString());
        $to       = $request->get('to',   now()->toDateString());
        $moduleId = $this->currentModuleId($request);

        $data  = $this->svc->profitAndLoss($from, $to, $moduleId);
        $scope = $this->scopeInfo($moduleId);

        if ($request->get('format') === 'excel') {
            $rows = new Collection();
            $rows->push(['REVENUE', '', '']);
            foreach ($data['revenue_rows'] as $r) {
                $rows->push([$r->account_code, $r->account_name, number_format($r->amount, 2)]);
            }
            $rows->push(['', 'Total Revenue', number_format($data['total_revenue'], 2)]);
            $rows->push(['', '', '']);
            $rows->push(['EXPENSES', '', '']);
            foreach ($data['expense_rows'] as $r) {
                $rows->push([$r->account_code, $r->account_name, number_format($r->amount, 2)]);
            }
            $rows->push(['', 'Total Expenses', number_format($data['total_expenses'], 2)]);
            $rows->push(['', '', '']);
            $rows->push(['', 'NET PROFIT / (LOSS)', number_format($data['net_profit'], 2)]);
            return Excel::download(
                new AccountingExport($rows, ['Code', 'Account', 'Amount']),
                "profit-loss-{$from}-{$to}.xlsx"
            );
        }

        if ($request->get('format') === 'pdf') {
            return $this->pdf('accounts::admin.reports.profit-loss', array_merge($data, compact('from', 'to')), "profit-loss-{$from}-{$to}.pdf");
        }

        return view('accounts::admin.reports.profit-loss', array_merge($data, compact('from', 'to', 'scope')));
    }

    // ── 8.02  Balance Sheet ───────────────────────────────────────────────────

    public function balanceSheet(Request $request)
    {
        $date     = $request->get('date', now()->toDateString());
        $moduleId = $this->currentModuleId($request);

        $data  = $this->svc->balanceSheet($date, $moduleId);
        $scope = $this->scopeInfo($moduleId);

        if ($request->get('format') === 'excel') {
            $rows = new Collection();
            $rows->push(['ASSETS', '', '']);
            foreach ($data['asset_rows'] as $r) {
                $rows->push([$r->account_code, $r->account_name, number_format($r->balance, 2)]);
            }
            $rows->push(['', 'Total Assets', number_format($data['total_assets'], 2)]);
            $rows->push(['', '', '']);
            $rows->push(['LIABILITIES', '', '']);
            foreach ($data['liability_rows'] as $r) {
                $rows->push([$r->account_code, $r->account_name, number_format($r->balance, 2)]);
            }
            $rows->push(['', 'Total Liabilities', number_format($data['total_liabilities'], 2)]);
            $rows->push(['', '', '']);
            $rows->push(['EQUITY', '', '']);
            foreach ($data['equity_rows'] as $r) {
                $rows->push([$r->account_code, $r->account_name, number_format($r->balance, 2)]);
            }
            $rows->push(['', 'Current Period Net Profit', number_format($data['net_profit'], 2)]);
            $rows->push(['', 'Total Equity', number_format($data['total_equity'], 2)]);
            return Excel::download(
                new AccountingExport($rows, ['Code', 'Account', 'Amount']),
                "balance-sheet-{$date}.xlsx"
            );
        }

        if ($request->get('format') === 'pdf') {
            return $this->pdf('accounts::admin.reports.balance-sheet', array_merge($data, compact('date')), "balance-sheet-{$date}.pdf");
        }

        return view('accounts::admin.reports.balance-sheet', array_merge($data, compact('date', 'scope')));
    }

    // ── 8.05  Tax Report ──────────────────────────────────────────────────────

    public function taxReport(Request $request)
    {
        $from     = $request->get('from', now()->startOfMonth()->toDateString());
        $to       = $request->get('to',   now()->toDateString());
        $moduleId = $this->currentModuleId($request);

        $data  = $this->svc->taxReport($from, $to, $moduleId);
        $scope = $this->scopeInfo($moduleId);

        if ($request->get('format') === 'excel') {
            $rows = $data['rows']->map(fn($r) => [
                \Carbon\Carbon::parse($r->posted_at)->format('Y-m-d'),
                $r->entry_number, $r->event_type,
                $r->reference_type ? "{$r->reference_type} #{$r->reference_id}" : '',
                $r->description ?? '',
                $r->debit  > 0 ? number_format($r->debit,  2) : '',
                $r->credit > 0 ? number_format($r->credit, 2) : '',
            ]);
            return Excel::download(
                new AccountingExport(collect($rows), ['Date', 'Entry #', 'Event', 'Reference', 'Description', 'Remitted', 'Collected']),
                "tax-report-{$from}-{$to}.xlsx"
            );
        }

        if ($request->get('format') === 'pdf') {
            return $this->pdf('accounts::admin.reports.tax-report', array_merge($data, compact('from', 'to')), "tax-report-{$from}-{$to}.pdf");
        }

        return view('accounts::admin.reports.tax-report', array_merge($data, compact('from', 'to', 'scope')));
    }

    // ── 8.06  COD Reconciliation ──────────────────────────────────────────────

    public function codReconciliation(Request $request)
    {
        $from     = $request->get('from', now()->startOfMonth()->toDateString());
        $to       = $request->get('to',   now()->toDateString());
        $moduleId = $this->currentModuleId($request);

        $data  = $this->svc->codReconciliation($from, $to, $moduleId);
        $scope = $this->scopeInfo($moduleId);

        if ($request->get('format') === 'excel') {
            $rows = $data['rows']->map(fn($r) => [
                \Carbon\Carbon::parse($r->posted_at)->format('Y-m-d'),
                $r->entry_number, $r->account_code, $r->event_type,
                $r->reference_type ? "{$r->reference_type} #{$r->reference_id}" : '',
                $r->debit  > 0 ? number_format($r->debit,  2) : '',
                $r->credit > 0 ? number_format($r->credit, 2) : '',
            ]);
            return Excel::download(
                new AccountingExport(collect($rows), ['Date', 'Entry #', 'Account', 'Event', 'Reference', 'Owed (DR)', 'Settled (CR)']),
                "cod-reconciliation-{$from}-{$to}.xlsx"
            );
        }

        if ($request->get('format') === 'pdf') {
            return $this->pdf('accounts::admin.reports.cod-reconciliation', array_merge($data, compact('from', 'to')), "cod-reconciliation-{$from}-{$to}.pdf");
        }

        return view('accounts::admin.reports.cod-reconciliation', array_merge($data, compact('from', 'to', 'scope')));
    }

    // ── 8.07  Gateway Reconciliation ─────────────────────────────────────────

    public function gatewayReconciliation(Request $request)
    {
        $from        = $request->get('from', now()->startOfMonth()->toDateString());
        $to          = $request->get('to',   now()->toDateString());
        $accountCode = $request->get('account', '1013');
        $moduleId    = $this->currentModuleId($request);

        $data  = $this->svc->gatewayReconciliation($from, $to, $accountCode, $moduleId);
        $scope = $this->scopeInfo($moduleId);

        if ($request->get('format') === 'excel') {
            $rows = $data['rows']->map(fn($r) => [
                \Carbon\Carbon::parse($r->posted_at)->format('Y-m-d'),
                $r->entry_number, $r->event_type,
                $r->reference_type ? "{$r->reference_type} #{$r->reference_id}" : '',
                $r->description ?? '',
                $r->debit  > 0 ? number_format($r->debit,  2) : '',
                $r->credit > 0 ? number_format($r->credit, 2) : '',
            ]);
            return Excel::download(
                new AccountingExport(collect($rows), ['Date', 'Entry #', 'Event', 'Reference', 'Description', 'In (DR)', 'Out (CR)']),
                "gateway-reconciliation-{$accountCode}-{$from}-{$to}.xlsx"
            );
        }

        if ($request->get('format') === 'pdf') {
            return $this->pdf('accounts::admin.reports.gateway-reconciliation', array_merge($data, compact('from', 'to', 'accountCode')), "gateway-reconciliation-{$accountCode}-{$from}-{$to}.pdf");
        }

        return view('accounts::admin.reports.gateway-reconciliation', array_merge($data, compact('from', 'to', 'accountCode', 'scope')));
    }

    // ── Statement Excel helper ────────────────────────────────────────────────

    private function exportStatement(array $statement, string $filename)
    {
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
            $filename
        );
    }

    // ── PDF helper ────────────────────────────────────────────────────────────

    private function pdf(string $view, array $data, string $filename)
    {
        $html = view($view, $data)->render();

        $mpdf = new \Mpdf\Mpdf([
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 12,
            'margin_right'  => 12,
        ]);
        $mpdf->SetTitle(str_replace(['-', '.pdf'], [' ', ''], $filename));
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
