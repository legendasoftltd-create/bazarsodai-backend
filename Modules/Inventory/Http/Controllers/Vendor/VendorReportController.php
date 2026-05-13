<?php

namespace Modules\Inventory\Http\Controllers\Vendor;

use App\Models\Item;
use App\Models\Store;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Entities\StockBatch;
use Modules\Inventory\Entities\StockMovement;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\InventoryAdjustment;
use Modules\Inventory\Exports\InventoryExport;

class VendorReportController extends Controller
{
    protected function storeId(): int
    {
        return Helpers::get_store_id();
    }

    public function stockLedger(Request $request)
    {
        $storeId = $this->storeId();
        $items   = Item::where('store_id', $storeId)->where('status', 1)->get(['id', 'name']);

        $movements = collect();
        if ($request->item_id) {
            $movements = StockMovement::where('item_id', $request->item_id)
                ->where('store_id', $storeId)
                ->when($request->type, fn($q) => $q->where('type', $request->type))
                ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
                ->when($request->to,   fn($q) => $q->whereDate('created_at', '<=', $request->to))
                ->latest()->paginate(50)->withQueryString();
        }

        return view('inventory::vendor.reports.stock-ledger', compact('items', 'movements'));
    }

    public function valuation(Request $request)
    {
        $storeId = $this->storeId();

        $items = Item::where('store_id', $storeId)
            ->where('stock', '>', 0)
            ->paginate(30)->withQueryString();

        $totalValue = Item::where('store_id', $storeId)->sum('total_stock_value');

        return view('inventory::vendor.reports.valuation', compact('items', 'totalValue'));
    }

    public function lowStock(Request $request)
    {
        $storeId  = $this->storeId();
        $store    = Store::findOrFail($storeId);
        $threshold = $store->config?->minimum_stock_for_warning ?? 10;

        $items = Item::where('store_id', $storeId)
            ->where('stock', '>', 0)
            ->where('stock', '<=', $threshold)
            ->latest()->paginate(30)->withQueryString();

        return view('inventory::vendor.reports.low-stock', compact('items', 'threshold'));
    }

    public function expiring(Request $request)
    {
        $storeId = $this->storeId();
        $days    = $request->input('days', 30);

        $batches = StockBatch::with('item')
            ->where('store_id', $storeId)
            ->where('qty_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days))
            ->orderBy('expires_at')
            ->paginate(30)->withQueryString();

        return view('inventory::vendor.reports.expiring', compact('batches', 'days'));
    }

    public function purchases(Request $request)
    {
        $storeId = $this->storeId();

        $movements = StockMovement::with('item')
            ->where('store_id', $storeId)
            ->whereIn('type', ['purchase', 'purchase_return'])
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()->paginate(30)->withQueryString();

        return view('inventory::vendor.reports.purchases', compact('movements'));
    }

    public function damageLoss(Request $request)
    {
        $storeId = $this->storeId();

        $movements = StockMovement::with('item')
            ->where('store_id', $storeId)
            ->whereIn('type', ['damaged', 'broken', 'internal_use'])
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()->paginate(30)->withQueryString();

        $totalLoss = StockMovement::where('store_id', $storeId)
            ->whereIn('type', ['damaged', 'broken', 'internal_use'])
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->sum('total_cost');

        return view('inventory::vendor.reports.damage-loss', compact('movements', 'totalLoss'));
    }

    public function adjustmentHistory(Request $request)
    {
        $storeId = $this->storeId();

        $adjustments = InventoryAdjustment::where('store_id', $storeId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->from,   fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,     fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()->paginate(30)->withQueryString();

        return view('inventory::vendor.reports.adjustment-history', compact('adjustments'));
    }

    public function movements(Request $request)
    {
        $storeId = $this->storeId();

        $movements = StockMovement::with('item')
            ->where('store_id', $storeId)
            ->when($request->type,    fn($q) => $q->where('type', $request->type))
            ->when($request->item_id, fn($q) => $q->where('item_id', $request->item_id))
            ->when($request->from,    fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,      fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()->paginate(30)->withQueryString();

        $items = Item::where('store_id', $storeId)->where('status', 1)->get(['id', 'name']);

        return view('inventory::vendor.reports.movements', compact('movements', 'items'));
    }

    public function export(Request $request, string $type)
    {
        $storeId = $this->storeId();

        [$rows, $headings, $filename] = $this->buildExportData($request, $type, $storeId);

        return Excel::download(
            new InventoryExport(collect($rows), $headings),
            $filename . '.xlsx'
        );
    }

    protected function buildExportData(Request $request, string $type, int $storeId): array
    {
        switch ($type) {
            case 'stock-ledger':
            case 'movements':
                $data = StockMovement::with('item')->where('store_id', $storeId)
                    ->when($request->type,    fn($q) => $q->where('type', $request->type))
                    ->when($request->item_id, fn($q) => $q->where('item_id', $request->item_id))
                    ->when($request->from,    fn($q) => $q->whereDate('created_at', '>=', $request->from))
                    ->when($request->to,      fn($q) => $q->whereDate('created_at', '<=', $request->to))
                    ->latest()->get()
                    ->map(fn($m) => [$m->created_at->format('Y-m-d H:i'), $m->item?->name, $m->type, $m->qty, $m->unit_cost, abs($m->total_cost ?? 0), $m->note]);
                return [$data, ['Date','Item','Type','Qty','Unit Cost','Total Cost','Note'], 'movements'];

            case 'valuation':
                $data = Item::where('store_id', $storeId)->where('stock','>',0)->get()
                    ->map(fn($i) => [$i->name, $i->stock, $i->average_cost ?? 0, $i->total_stock_value ?? 0, $i->valuation_method ?? 'default']);
                return [$data, ['Item','Stock','Avg Cost','Total Value','Method'], 'valuation'];

            case 'low-stock':
                $threshold = Store::find($storeId)?->config?->minimum_stock_for_warning ?? 10;
                $data = Item::where('store_id', $storeId)->where('stock','>',0)->where('stock','<=',$threshold)->get()
                    ->map(fn($i) => [$i->name, $i->stock, $threshold]);
                return [$data, ['Item','Current Stock','Threshold'], 'low-stock'];

            case 'expiring':
                $days = $request->input('days', 30);
                $data = StockBatch::with('item')->where('store_id', $storeId)->where('qty_remaining','>',0)
                    ->whereNotNull('expires_at')->where('expires_at','<=',now()->addDays($days))->orderBy('expires_at')->get()
                    ->map(fn($b) => [$b->item?->name, $b->batch_number, $b->qty_remaining, $b->unit_cost, $b->expires_at?->format('Y-m-d'), (int)now()->diffInDays($b->expires_at,false)]);
                return [$data, ['Item','Batch','Qty Remaining','Unit Cost','Expires','Days Left'], 'expiring'];

            case 'purchases':
                $data = StockMovement::with('item')->where('store_id',$storeId)->whereIn('type',['purchase','purchase_return'])
                    ->when($request->from, fn($q) => $q->whereDate('created_at','>=',$request->from))
                    ->when($request->to,   fn($q) => $q->whereDate('created_at','<=',$request->to))
                    ->latest()->get()
                    ->map(fn($m) => [$m->created_at->format('Y-m-d'), $m->item?->name, $m->type, $m->qty, $m->unit_cost, abs($m->total_cost ?? 0), $m->note]);
                return [$data, ['Date','Item','Type','Qty','Unit Cost','Total','Note'], 'purchases'];

            case 'damage-loss':
                $data = StockMovement::with('item')->where('store_id',$storeId)->whereIn('type',['damaged','broken','internal_use'])
                    ->when($request->from, fn($q) => $q->whereDate('created_at','>=',$request->from))
                    ->when($request->to,   fn($q) => $q->whereDate('created_at','<=',$request->to))
                    ->latest()->get()
                    ->map(fn($m) => [$m->created_at->format('Y-m-d'), $m->item?->name, $m->type, abs($m->qty), $m->unit_cost, abs($m->total_cost ?? 0), $m->note]);
                return [$data, ['Date','Item','Type','Qty','Unit Cost','Loss Value','Note'], 'damage-loss'];

            case 'adjustment-history':
                $data = InventoryAdjustment::where('store_id',$storeId)
                    ->when($request->from, fn($q) => $q->whereDate('created_at','>=',$request->from))
                    ->when($request->to,   fn($q) => $q->whereDate('created_at','<=',$request->to))
                    ->latest()->get()
                    ->map(fn($a) => [$a->adjustment_number, $a->status, $a->created_at->format('Y-m-d'), $a->note]);
                return [$data, ['Ref #','Status','Date','Note'], 'adjustment-history'];

            default:
                return [collect(), ['#'], 'export'];
        }
    }
}
