<?php

namespace Modules\Inventory\Http\Controllers\Admin;

use App\Models\Item;
use App\Models\Store;
use App\Models\Module;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Inventory\Entities\StockBatch;
use Modules\Inventory\Entities\StockMovement;
use Modules\Inventory\Entities\ReorderPoint;
use Modules\Inventory\Services\StockService;
use Modules\Inventory\Services\ValuationService;

class CentralInventoryController extends Controller
{
    public function __construct(
        protected StockService $stock,
        protected ValuationService $valuation
    ) {}

    /** Central inventory — all modules, all vendors */
    public function index(Request $request)
    {
        $query = Item::with(['store', 'store.config'])
            ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
            ->when($request->store_id,  fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->search,    fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->stock_status === 'low', fn($q) => $q->whereColumn('stock', '<=', 'store_configs.minimum_stock_for_warning')->join('store_configs', 'store_configs.store_id', 'items.store_id'))
            ->when($request->stock_status === 'out', fn($q) => $q->where('stock', '<=', 0))
            ->latest();

        $items   = $query->paginate(20)->withQueryString();
        $modules = Module::all();
        $stores  = Store::active()->get();
        $totalValue = Item::sum(\Illuminate\Support\Facades\DB::raw('stock * average_cost'));

        return view('inventory::admin.central.index', compact('items', 'modules', 'stores', 'totalValue'));
    }

    /** Module-wise inventory */
    public function byModule(Request $request, int $moduleId)
    {
        $module  = Module::findOrFail($moduleId);
        $items   = Item::with('store')
            ->where('module_id', $moduleId)
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->search,   fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stores = Store::whereHas('items', fn($q) => $q->where('module_id', $moduleId))->get();

        return view('inventory::admin.central.by-module', compact('items', 'module', 'stores'));
    }

    /** Vendor-wise inventory */
    public function byVendor(Request $request, int $storeId)
    {
        $store   = Store::with('config')->findOrFail($storeId);
        $items   = Item::where('store_id', $storeId)
            ->when($request->module_id, fn($q) => $q->where('module_id', $request->module_id))
            ->when($request->search,    fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $modules = Module::all();

        return view('inventory::admin.central.by-vendor', compact('items', 'store', 'modules'));
    }

    /** Module + vendor combined */
    public function byModuleVendor(Request $request, int $moduleId, int $storeId)
    {
        $module  = Module::findOrFail($moduleId);
        $store   = Store::findOrFail($storeId);
        $items   = Item::where('module_id', $moduleId)
            ->where('store_id', $storeId)
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('inventory::admin.central.by-module-vendor', compact('items', 'module', 'store'));
    }

    /** Item drill-down: movement history + batch list */
    public function itemDetail(Request $request, int $itemId)
    {
        $item = Item::with('store')->findOrFail($itemId);

        $movements = StockMovement::where('item_id', $itemId)
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $reorderPoint = ReorderPoint::where('item_id', $itemId)
            ->where('store_id', $item->store_id)
            ->first();

        $batches = StockBatch::where('item_id', $itemId)
            ->where('store_id', $item->store_id)
            ->where('qty_remaining', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $totalValue = $this->valuation->calculateStockValue($item);

        return view('inventory::admin.central.item-detail', compact('item', 'movements', 'reorderPoint', 'totalValue', 'batches'));
    }

    /** Enter opening stock */
    public function openingStock(Request $request)
    {
        $request->validate([
            'item_id'       => 'required|exists:items,id',
            'qty'           => 'required|numeric|min:0.01',
            'unit_cost'     => 'required|numeric|min:0',
            'note'          => 'nullable|string|max:500',
            'variation_key' => 'nullable|string',
        ]);

        $item = Item::findOrFail($request->item_id);

        // Only allow once if stock is 0
        if ($item->stock > 0) {
            return back()->with('error', 'Opening stock can only be set when current stock is zero.');
        }

        $this->stock->add(
            itemId:        $request->item_id,
            qty:           $request->qty,
            storeId:       $item->store_id,
            type:          'opening',
            unitCost:      $request->unit_cost,
            variationKey:  $request->variation_key,
            referenceType: null,
            referenceId:   null,
            note:          $request->note ?? 'Opening stock entry'
        );

        return back()->with('success', 'Opening stock saved successfully.');
    }

    /** Record damaged stock */
    public function damaged(Request $request)
    {
        return $this->specialTransaction($request, 'damaged', 'Damaged stock recorded.');
    }

    /** Record broken stock */
    public function broken(Request $request)
    {
        return $this->specialTransaction($request, 'broken', 'Broken stock recorded.');
    }

    /** Record internal use */
    public function internalUse(Request $request)
    {
        return $this->specialTransaction($request, 'internal_use', 'Internal use recorded.');
    }

    /** Save item-level valuation method override */
    public function saveItemValuation(Request $request, int $itemId)
    {
        $request->validate(['valuation_method' => 'required|in:average,fifo,lifo,']);
        Item::findOrFail($itemId)->update(['valuation_method' => $request->valuation_method ?: null]);
        return back()->with('success', 'Valuation method saved.');
    }

    /** Save store-level valuation method in store_configs */
    public function saveStoreValuation(Request $request, int $storeId)
    {
        $request->validate(['inventory_valuation_method' => 'required|in:average,fifo,lifo']);
        $store = Store::with('config')->findOrFail($storeId);
        if ($store->config) {
            $store->config->update(['inventory_valuation_method' => $request->inventory_valuation_method]);
        }
        return back()->with('success', 'Store valuation method updated.');
    }

    protected function specialTransaction(Request $request, string $type, string $successMsg)
    {
        $request->validate([
            'item_id'       => 'required|exists:items,id',
            'qty'           => 'required|numeric|min:0.01',
            'note'          => 'nullable|string|max:500',
            'variation_key' => 'nullable|string',
        ]);

        $item = Item::findOrFail($request->item_id);

        $this->stock->deduct(
            itemId:        $request->item_id,
            qty:           $request->qty,
            storeId:       $item->store_id,
            type:          $type,
            variationKey:  $request->variation_key,
            referenceType: null,
            referenceId:   null,
            note:          $request->note
        );

        return back()->with('success', $successMsg);
    }
}
