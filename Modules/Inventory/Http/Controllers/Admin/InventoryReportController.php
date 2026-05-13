<?php

namespace Modules\Inventory\Http\Controllers\Admin;

use App\Models\Item;
use App\Models\Store;
use App\Models\Module;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Entities\StockBatch;
use Modules\Inventory\Entities\StockMovement;
use Modules\Inventory\Entities\StockTransfer;
use Modules\Inventory\Entities\InventoryAdjustment;
use Modules\Inventory\Entities\ReorderPoint;
use Modules\Inventory\Exports\InventoryExport;

class InventoryReportController extends Controller
{
    /** Stock ledger — all movements for an item or store */
    public function stockLedger(Request $request)
    {
        $movements = StockMovement::with(['item', 'store'])
            ->when($request->item_id,   fn($q) => $q->where('item_id', $request->item_id))
            ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
            ->when($request->type,      fn($q) => $q->where('type', $request->type))
            ->when($request->from,      fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,        fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stores  = Store::active()->get();
        $modules = Module::all();

        return view('inventory::admin.reports.stock-ledger', compact('movements', 'stores', 'modules'));
    }

    /** Current stock valuation */
    public function valuation(Request $request)
    {
        $items = Item::with(['store', 'module'])
            ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
            ->where('stock', '>', 0)
            ->paginate(25)
            ->withQueryString();

        $totalValue = Item::when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
            ->sum(DB::raw('total_stock_value'));

        $stores  = Store::active()->get();
        $modules = Module::all();

        return view('inventory::admin.reports.valuation', compact('items', 'totalValue', 'stores', 'modules'));
    }

    /** Low stock / reorder report */
    public function lowStock(Request $request)
    {
        $filter  = $request->input('filter', 'reorder');
        $storeId = $request->store_id;

        $query = Item::with(['store', 'reorderPoints'])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $itemsQuery = (clone $query);

        if ($filter === 'out') {
            $itemsQuery->where('stock', '<=', 0);
        } elseif ($filter === 'low') {
            $itemsQuery->join('store_configs', 'store_configs.store_id', 'items.store_id')
                ->where('items.stock', '>', 0)
                ->whereColumn('items.stock', '<=', 'store_configs.minimum_stock_for_warning')
                ->select('items.*');
        } else {
            $itemsWithReorder = ReorderPoint::when($storeId, fn($q) => $q->where('store_id', $storeId))->pluck('item_id');
            $itemsQuery->whereIn('id', $itemsWithReorder)
                ->whereExists(function ($sub) use ($storeId) {
                    $sub->from('reorder_points')
                        ->whereColumn('reorder_points.item_id', 'items.id')
                        ->when($storeId, fn($q) => $q->where('reorder_points.store_id', $storeId))
                        ->whereColumn('items.stock', '<=', 'reorder_points.reorder_at');
                });
        }

        $items = $itemsQuery->paginate(25)->withQueryString();

        $totalItems        = $query->count();
        $outOfStockCount   = (clone $query)->where('stock', '<=', 0)->count();
        $belowReorderCount = ReorderPoint::with('item')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->get()->filter(fn($rp) => $rp->item && $rp->item->stock <= $rp->reorder_at)->count();
        $lowStockCount = Item::when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->join('store_configs', 'store_configs.store_id', 'items.store_id')
            ->where('items.stock', '>', 0)
            ->whereColumn('items.stock', '<=', 'store_configs.minimum_stock_for_warning')
            ->count();

        $stores = Store::active()->get(['id', 'name']);

        return view('inventory::admin.reports.low-stock', compact(
            'items', 'stores', 'totalItems',
            'outOfStockCount', 'belowReorderCount', 'lowStockCount'
        ));
    }

    /** Expiring stock */
    public function expiring(Request $request)
    {
        $days    = (int) ($request->days ?? 30);
        $storeId = $request->store_id;

        $baseQuery = StockBatch::with(['item.store'])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $batches = (clone $baseQuery)
            ->whereNotNull('expires_at')
            ->where('qty_remaining', '>', 0)
            ->where('expires_at', '<=', now()->addDays($days))
            ->orderBy('expires_at')
            ->paginate(25)
            ->withQueryString();

        $expiringCount      = (clone $baseQuery)->available()->expiringSoon($days)->count();
        $expiredCount       = (clone $baseQuery)->available()->expired()->count();
        $totalExpiringQty   = (clone $baseQuery)->available()->expiringSoon($days)->sum('qty_remaining');
        $totalExpiringValue = (clone $baseQuery)->available()->expiringSoon($days)
            ->selectRaw('SUM(qty_remaining * unit_cost)')->value('SUM(qty_remaining * unit_cost)') ?? 0;

        $stores = Store::active()->get(['id', 'name']);

        return view('inventory::admin.reports.expiring', compact(
            'batches', 'stores', 'days',
            'expiringCount', 'expiredCount', 'totalExpiringQty', 'totalExpiringValue'
        ));
    }

    /** Dead stock — no movement in X days */
    public function deadStock(Request $request)
    {
        $days   = $request->days ?? 60;
        $cutoff = now()->subDays($days);

        $activeItemIds = StockMovement::where('created_at', '>=', $cutoff)
            ->where('type', 'sale')
            ->pluck('item_id');

        $items = Item::with(['store', 'module'])
            ->where('stock', '>', 0)
            ->whereNotIn('id', $activeItemIds)
            ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
            ->paginate(25)
            ->withQueryString();

        $stores  = Store::active()->get();
        $modules = Module::all();

        return view('inventory::admin.reports.dead-stock', compact('items', 'stores', 'modules', 'days'));
    }

    /** Purchase summary */
    public function purchases(Request $request)
    {
        $movements = StockMovement::with(['item', 'store'])
            ->whereIn('type', ['purchase', 'purchase_return'])
            ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
            ->when($request->from,      fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,        fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stores  = Store::active()->get();
        $modules = Module::all();

        return view('inventory::admin.reports.purchases', compact('movements', 'stores', 'modules'));
    }

    /** Damage/Broken/Loss report */
    public function damageLoss(Request $request)
    {
        $movements = StockMovement::with(['item', 'store'])
            ->whereIn('type', ['damaged', 'broken', 'internal_use'])
            ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
            ->when($request->type,      fn($q) => $q->where('type', $request->type))
            ->when($request->from,      fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,        fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $totalLoss = StockMovement::whereIn('type', ['damaged', 'broken', 'internal_use'])
            ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
            ->when($request->type,      fn($q) => $q->where('type', $request->type))
            ->when($request->from,      fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,        fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->sum('total_cost');

        $stores  = Store::active()->get();
        $modules = Module::all();

        return view('inventory::admin.reports.damage-loss', compact('movements', 'totalLoss', 'stores', 'modules'));
    }

    /** Valuation summary grouped by method (FIFO / LIFO / Average) */
    public function valuationSummary(Request $request)
    {
        $storeId  = $request->store_id;
        $moduleId = $request->module_id;

        $rows = Item::with('store')
            ->when($storeId,  fn($q) => $q->where('store_id', $storeId))
            ->when($moduleId, fn($q) => $q->where('module_id', $moduleId))
            ->where('stock', '>', 0)
            ->get();

        $byMethod = $rows->groupBy(fn($i) => $i->valuation_method ?? 'store_default')->map(fn($g) => [
            'count'       => $g->count(),
            'total_stock' => $g->sum('stock'),
            'total_value' => $g->sum('total_stock_value'),
        ]);

        $grandTotal = $rows->sum('total_stock_value');

        $stores  = Store::active()->get();
        $modules = Module::all();

        return view('inventory::admin.reports.valuation-summary',
            compact('byMethod', 'grandTotal', 'stores', 'modules', 'rows'));
    }

    /** Module-wise stock summary */
    public function moduleStock(Request $request)
    {
        $modules = Module::all();

        $summary = $modules->map(function ($module) use ($request) {
            $q = Item::where('module_id', $module->id)
                ->when($request->store_id, fn($q2) => $q2->where('store_id', $request->store_id));

            return [
                'module'       => $module,
                'item_count'   => $q->count(),
                'in_stock'     => (clone $q)->where('stock', '>', 0)->count(),
                'out_of_stock' => (clone $q)->where('stock', '<=', 0)->count(),
                'total_value'  => (clone $q)->sum('total_stock_value'),
            ];
        })->filter(fn($r) => $r['item_count'] > 0)->values();

        $stores = Store::active()->get();

        return view('inventory::admin.reports.module-summary', compact('summary', 'stores'));
    }

    /** Vendor-wise stock summary */
    public function vendorStock(Request $request)
    {
        $storesQuery = Store::active()
            ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
            ->get();

        $summary = $storesQuery->map(function ($store) {
            $q = Item::where('store_id', $store->id);
            return [
                'store'        => $store,
                'item_count'   => $q->count(),
                'in_stock'     => (clone $q)->where('stock', '>', 0)->count(),
                'out_of_stock' => (clone $q)->where('stock', '<=', 0)->count(),
                'total_value'  => (clone $q)->sum('total_stock_value'),
            ];
        })->filter(fn($r) => $r['item_count'] > 0)->values();

        $modules = Module::all();

        return view('inventory::admin.reports.vendor-summary', compact('summary', 'modules'));
    }

    /** Adjustment history report */
    public function adjustmentHistory(Request $request)
    {
        $adjustments = InventoryAdjustment::with('store')
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->from,     fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,       fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stores = Store::active()->get();

        return view('inventory::admin.reports.adjustment-history', compact('adjustments', 'stores'));
    }

    /** Transfer history report */
    public function transferHistory(Request $request)
    {
        $transfers = StockTransfer::with(['fromStore', 'toStore'])
            ->when($request->store_id, fn($q) => $q->where('from_store_id', $request->store_id)
                ->orWhere('to_store_id', $request->store_id))
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->from,     fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,       fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stores = Store::active()->get();

        return view('inventory::admin.reports.transfer-history', compact('transfers', 'stores'));
    }

    /** COGS — cost of goods sold via confirmed orders */
    public function cogs(Request $request)
    {
        $from    = $request->from ?? now()->startOfMonth()->toDateString();
        $to      = $request->to   ?? now()->toDateString();
        $storeId = $request->store_id;

        $rows = StockMovement::with(['item', 'store'])
            ->where('type', 'sale')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $totalCogs = StockMovement::where('type', 'sale')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->sum('total_cost');

        $stores = Store::active()->get();

        return view('inventory::admin.reports.cogs', compact('rows', 'totalCogs', 'stores', 'from', 'to'));
    }

    /** All movements */
    public function movements(Request $request)
    {
        return $this->stockLedger($request);
    }

    /** Export — Excel for tabular reports, PDF for valuation-summary and cogs */
    public function export(Request $request, string $type)
    {
        $isPdf = in_array($type, ['valuation-summary', 'cogs']);

        if ($isPdf) {
            return $this->exportPdf($request, $type);
        }

        [$rows, $headings, $filename] = $this->buildExportData($request, $type);

        return Excel::download(
            new InventoryExport(collect($rows), $headings),
            $filename . '.xlsx'
        );
    }

    protected function buildExportData(Request $request, string $type): array
    {
        switch ($type) {
            case 'stock-ledger':
            case 'movements':
                $data = StockMovement::with(['item', 'store'])
                    ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
                    ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
                    ->when($request->type,      fn($q) => $q->where('type', $request->type))
                    ->when($request->from,      fn($q) => $q->whereDate('created_at', '>=', $request->from))
                    ->when($request->to,        fn($q) => $q->whereDate('created_at', '<=', $request->to))
                    ->latest()->get()
                    ->map(fn($m) => [
                        $m->created_at->format('Y-m-d H:i'),
                        $m->item?->name,
                        $m->store?->name,
                        $m->type,
                        $m->qty,
                        $m->unit_cost,
                        abs($m->total_cost ?? ($m->qty * $m->unit_cost)),
                        $m->note,
                    ]);
                return [$data, ['Date','Item','Vendor','Type','Qty','Unit Cost','Total Cost','Note'], 'stock-ledger'];

            case 'valuation':
                $data = Item::with(['store','module'])
                    ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
                    ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
                    ->where('stock', '>', 0)->get()
                    ->map(fn($i) => [
                        $i->name, $i->store?->name, $i->module?->module_name,
                        $i->stock, $i->average_cost ?? 0, $i->total_stock_value ?? 0,
                        $i->valuation_method ?? 'default',
                    ]);
                return [$data, ['Item','Vendor','Module','Stock','Avg Cost','Total Value','Valuation'], 'stock-valuation'];

            case 'dead-stock':
                $days = $request->days ?? 60;
                $ids  = StockMovement::where('created_at', '>=', now()->subDays($days))->where('type','sale')->pluck('item_id');
                $data = Item::with(['store','module'])->where('stock','>',0)->whereNotIn('id',$ids)
                    ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
                    ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
                    ->get()->map(fn($i) => [$i->name, $i->store?->name, $i->module?->module_name, $i->stock, $i->total_stock_value ?? 0]);
                return [$data, ['Item','Vendor','Module','Stock','Value'], 'dead-stock'];

            case 'purchases':
                $data = StockMovement::with(['item','store'])
                    ->whereIn('type', ['purchase','purchase_return'])
                    ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
                    ->when($request->from,      fn($q) => $q->whereDate('created_at', '>=', $request->from))
                    ->when($request->to,        fn($q) => $q->whereDate('created_at', '<=', $request->to))
                    ->latest()->get()
                    ->map(fn($m) => [$m->created_at->format('Y-m-d'), $m->item?->name, $m->store?->name, $m->type, $m->qty, $m->unit_cost, abs($m->total_cost ?? 0), $m->note]);
                return [$data, ['Date','Item','Vendor','Type','Qty','Unit Cost','Total','Note'], 'purchases'];

            case 'damage-loss':
                $data = StockMovement::with(['item','store'])
                    ->whereIn('type', ['damaged','broken','internal_use'])
                    ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
                    ->when($request->type,      fn($q) => $q->where('type', $request->type))
                    ->when($request->from,      fn($q) => $q->whereDate('created_at', '>=', $request->from))
                    ->when($request->to,        fn($q) => $q->whereDate('created_at', '<=', $request->to))
                    ->latest()->get()
                    ->map(fn($m) => [$m->created_at->format('Y-m-d'), $m->item?->name, $m->store?->name, $m->type, abs($m->qty), $m->unit_cost, abs($m->total_cost ?? 0), $m->note]);
                return [$data, ['Date','Item','Vendor','Type','Qty','Unit Cost','Loss Value','Note'], 'damage-loss'];

            case 'expiring':
                $days = (int) ($request->days ?? 30);
                $data = StockBatch::with(['item.store'])
                    ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
                    ->whereNotNull('expires_at')->where('qty_remaining','>',0)
                    ->where('expires_at','<=',now()->addDays($days))->orderBy('expires_at')->get()
                    ->map(fn($b) => [$b->item?->name, $b->item?->store?->name, $b->batch_number, $b->qty_remaining, $b->unit_cost, $b->expires_at?->format('Y-m-d'), (int)now()->diffInDays($b->expires_at,false)]);
                return [$data, ['Item','Vendor','Batch','Qty Remaining','Unit Cost','Expires','Days Left'], 'expiring-stock'];

            case 'adjustment-history':
                $data = InventoryAdjustment::with('store')
                    ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
                    ->when($request->from,     fn($q) => $q->whereDate('created_at', '>=', $request->from))
                    ->when($request->to,       fn($q) => $q->whereDate('created_at', '<=', $request->to))
                    ->latest()->get()
                    ->map(fn($a) => [$a->adjustment_number, $a->store?->name, $a->status, $a->created_at->format('Y-m-d'), $a->note]);
                return [$data, ['Ref #','Vendor','Status','Date','Note'], 'adjustment-history'];

            case 'transfer-history':
                $data = StockTransfer::with(['fromStore','toStore'])
                    ->when($request->store_id, fn($q) => $q->where('from_store_id', $request->store_id)->orWhere('to_store_id', $request->store_id))
                    ->when($request->from,     fn($q) => $q->whereDate('created_at', '>=', $request->from))
                    ->when($request->to,       fn($q) => $q->whereDate('created_at', '<=', $request->to))
                    ->latest()->get()
                    ->map(fn($t) => [$t->transfer_number, $t->fromStore?->name, $t->toStore?->name, $t->status, $t->created_at->format('Y-m-d')]);
                return [$data, ['Ref #','From','To','Status','Date'], 'transfer-history'];

            default:
                return [collect(), ['#'], 'export'];
        }
    }

    protected function exportPdf(Request $request, string $type): \Illuminate\Http\Response
    {
        if ($type === 'valuation-summary') {
            $rows = Item::with('store')->where('stock', '>', 0)
                ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
                ->get();
            $byMethod   = $rows->groupBy(fn($i) => $i->valuation_method ?? 'store_default')->map(fn($g) => [
                'count' => $g->count(), 'total_stock' => $g->sum('stock'), 'total_value' => $g->sum('total_stock_value'),
            ]);
            $grandTotal = $rows->sum('total_stock_value');
            $html       = view('inventory::admin.reports.pdf.valuation-summary', compact('byMethod','grandTotal','rows'))->render();
        } else {
            $from    = $request->from ?? now()->startOfMonth()->toDateString();
            $to      = $request->to   ?? now()->toDateString();
            $rows    = StockMovement::with(['item','store'])->where('type','sale')
                ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
                ->whereDate('created_at','>=',$from)->whereDate('created_at','<=',$to)->latest()->get();
            $totalCogs = $rows->sum('total_cost');
            $html      = view('inventory::admin.reports.pdf.cogs', compact('rows','totalCogs','from','to'))->render();
        }

        $mpdf = new \Mpdf\Mpdf(['orientation' => 'L', 'margin_top' => 10, 'margin_bottom' => 10]);
        $mpdf->WriteHTML($html);

        $filename = $type . '-' . now()->format('Y-m-d') . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
