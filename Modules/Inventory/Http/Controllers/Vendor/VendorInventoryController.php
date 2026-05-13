<?php

namespace Modules\Inventory\Http\Controllers\Vendor;

use App\Models\Item;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Modules\Inventory\Entities\StockBatch;
use Modules\Inventory\Entities\StockMovement;
use Modules\Inventory\Entities\ReorderPoint;
use Modules\Inventory\Services\StockService;
use Modules\Inventory\Services\ValuationService;

class VendorInventoryController extends Controller
{
    public function __construct(
        protected StockService $stock,
        protected ValuationService $valuation
    ) {}

    protected function vendorStore(): Store
    {
        return Store::findOrFail(Helpers::get_store_id());
    }

    public function index(Request $request)
    {
        $store = $this->vendorStore();

        $items = Item::where('store_id', $store->id)
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->stock_status === 'low', fn($q) => $q->where('stock', '>', 0)->where('stock', '<=', $store->config?->minimum_stock_for_warning ?? 10))
            ->when($request->stock_status === 'out', fn($q) => $q->where('stock', '<=', 0))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totalValue = Item::where('store_id', $store->id)->sum('total_stock_value');

        return view('inventory::vendor.inventory.index', compact('items', 'store', 'totalValue'));
    }

    public function itemDetail(Request $request, int $itemId)
    {
        $store = $this->vendorStore();
        $item  = Item::where('store_id', $store->id)->findOrFail($itemId);

        $movements = StockMovement::where('item_id', $itemId)
            ->where('store_id', $store->id)
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $reorderPoint = ReorderPoint::where('item_id', $itemId)->where('store_id', $store->id)->first();
        $totalValue   = $this->valuation->calculateStockValue($item);
        $batches      = StockBatch::where('item_id', $itemId)->where('store_id', $store->id)->where('qty_remaining', '>', 0)->orderBy('created_at', 'asc')->get();

        return view('inventory::vendor.inventory.item-detail', compact('item', 'movements', 'reorderPoint', 'totalValue', 'batches'));
    }

    public function openingStock(Request $request)
    {
        $request->validate([
            'item_id'   => ['required', Rule::exists('items', 'id')->where('store_id', Helpers::get_store_id())],
            'qty'       => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'note'      => 'nullable|string|max:500',
        ]);

        $store = $this->vendorStore();
        $item  = Item::where('store_id', $store->id)->findOrFail($request->item_id);

        if ($item->stock > 0) {
            return back()->with('error', 'Opening stock can only be set when current stock is zero.');
        }

        $this->stock->add(
            itemId:    $request->item_id,
            qty:       $request->qty,
            storeId:   $store->id,
            type:      'opening',
            unitCost:  $request->unit_cost,
            note:      $request->note ?? 'Opening stock'
        );

        return back()->with('success', 'Opening stock saved.');
    }

    public function damaged(Request $request)
    {
        return $this->specialTransaction($request, 'damaged', 'Damaged stock recorded.');
    }

    public function broken(Request $request)
    {
        return $this->specialTransaction($request, 'broken', 'Broken stock recorded.');
    }

    public function internalUse(Request $request)
    {
        return $this->specialTransaction($request, 'internal_use', 'Internal use recorded.');
    }

    /** Save item-level valuation method override (vendor's own items only) */
    public function saveItemValuation(Request $request, int $itemId)
    {
        $request->validate(['valuation_method' => 'required|in:average,fifo,lifo,']);
        $store = $this->vendorStore();
        $item  = Item::where('store_id', $store->id)->findOrFail($itemId);
        $item->update(['valuation_method' => $request->valuation_method ?: null]);
        return back()->with('success', 'Valuation method saved.');
    }

    /** Save store-level valuation method */
    public function saveStoreValuation(Request $request)
    {
        $request->validate(['inventory_valuation_method' => 'required|in:average,fifo,lifo']);
        $store = $this->vendorStore();
        $store->load('config');
        if ($store->config) {
            $store->config->update(['inventory_valuation_method' => $request->inventory_valuation_method]);
        }
        return back()->with('success', 'Valuation method updated.');
    }

    public function reorderPoints(Request $request)
    {
        $store = $this->vendorStore();

        $reorderPoints = ReorderPoint::with('item')
            ->where('store_id', $store->id)
            ->when($request->search, fn($q) => $q->whereHas('item', fn($i) => $i->where('name', 'like', '%' . $request->search . '%')))
            ->latest()->paginate(20)->withQueryString();

        return view('inventory::vendor.reorder.index', compact('reorderPoints'));
    }

    public function deleteReorderPoint(int $id)
    {
        $rp = ReorderPoint::where('store_id', $this->vendorStore()->id)->findOrFail($id);
        $rp->delete();
        return back()->with('success', 'Reorder point removed.');
    }

    public function setReorderPoint(Request $request)
    {
        $request->validate([
            'item_id'    => ['required', Rule::exists('items', 'id')->where('store_id', Helpers::get_store_id())],
            'reorder_at' => 'required|numeric|min:0',
            'reorder_qty'=> 'required|numeric|min:0',
        ]);

        $store = $this->vendorStore();

        ReorderPoint::updateOrCreate(
            ['item_id' => $request->item_id, 'store_id' => $store->id, 'variation_key' => $request->variation_key],
            [
                'module_id'   => $store->module_id,
                'reorder_at'  => $request->reorder_at,
                'reorder_qty' => $request->reorder_qty,
                'auto_notify' => $request->boolean('auto_notify', true),
            ]
        );

        return back()->with('success', 'Reorder point saved.');
    }

    protected function specialTransaction(Request $request, string $type, string $msg)
    {
        $request->validate([
            'item_id'       => ['required', Rule::exists('items', 'id')->where('store_id', Helpers::get_store_id())],
            'qty'           => 'required|numeric|min:0.01',
            'note'          => 'nullable|string|max:500',
            'variation_key' => 'nullable|string',
        ]);

        $store = $this->vendorStore();
        Item::where('store_id', $store->id)->findOrFail($request->item_id);

        $this->stock->deduct(
            itemId:       $request->item_id,
            qty:          $request->qty,
            storeId:      $store->id,
            type:         $type,
            variationKey: $request->variation_key,
            note:         $request->note
        );

        return back()->with('success', $msg);
    }
}
