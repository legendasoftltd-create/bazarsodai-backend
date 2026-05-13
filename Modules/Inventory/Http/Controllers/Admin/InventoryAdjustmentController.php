<?php

namespace Modules\Inventory\Http\Controllers\Admin;

use App\Models\Item;
use App\Models\Store;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Inventory\Entities\InventoryAdjustment;
use Modules\Inventory\Entities\InventoryAdjustmentItem;
use Modules\Inventory\Services\StockService;

class InventoryAdjustmentController extends Controller
{
    public function __construct(protected StockService $stock) {}

    public function index(Request $request)
    {
        $adjustments = InventoryAdjustment::with('store')
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(20)->withQueryString();

        $stores = Store::active()->get();
        return view('inventory::admin.adjustment.index', compact('adjustments', 'stores'));
    }

    public function create()
    {
        $stores  = Store::active()->get();
        $modules = Module::all();
        $items   = Item::active()->get(['id', 'name', 'store_id', 'module_id', 'stock']);
        return view('inventory::admin.adjustment.create', compact('stores', 'modules', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id'      => 'required|exists:stores,id',
            'items'         => 'required|array|min:1',
            'items.*.item_id'      => 'required|exists:items,id',
            'items.*.system_qty'   => 'required|numeric|min:0',
            'items.*.physical_qty' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $store = Store::findOrFail($request->store_id);
            $adj   = InventoryAdjustment::create([
                'adjustment_number' => InventoryAdjustment::generateAdjustmentNumber(),
                'store_id'          => $request->store_id,
                'module_id'         => $store->module_id,
                'status'            => 'pending_approval',
                'note'              => $request->note,
                'created_by'        => auth()->id(),
            ]);

            foreach ($request->items as $line) {
                Item::where('store_id', $request->store_id)->findOrFail($line['item_id']);
                InventoryAdjustmentItem::create([
                    'adjustment_id' => $adj->id,
                    'item_id'       => $line['item_id'],
                    'variation_key' => $line['variation_key'] ?? null,
                    'system_qty'    => $line['system_qty'],
                    'physical_qty'  => $line['physical_qty'],
                    'difference'    => $line['physical_qty'] - $line['system_qty'],
                    'reason'        => $line['reason'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.inventory.adjustments.index')->with('success', 'Adjustment submitted for approval.');
    }

    public function show(InventoryAdjustment $adjustment)
    {
        $adjustment->load(['store', 'adjustmentItems.item']);
        return view('inventory::admin.adjustment.show', compact('adjustment'));
    }

    /** Approve and apply stock changes */
    public function approve(Request $request, int $id)
    {
        $adjustment = InventoryAdjustment::with('adjustmentItems')->findOrFail($id);

        if ($adjustment->status !== 'pending_approval') {
            return back()->with('error', 'Adjustment is not pending approval.');
        }

        DB::transaction(function () use ($adjustment) {
            foreach ($adjustment->adjustmentItems as $line) {
                $diff = $line->difference;
                if ($diff == 0) continue;

                if ($diff > 0) {
                    $this->stock->add(
                        itemId:        $line->item_id,
                        qty:           abs($diff),
                        storeId:       $adjustment->store_id,
                        type:          'adjustment_add',
                        variationKey:  $line->variation_key,
                        referenceType: InventoryAdjustment::class,
                        referenceId:   $adjustment->id,
                        note:          "Adjustment #{$adjustment->adjustment_number}"
                    );
                } else {
                    $this->stock->deduct(
                        itemId:        $line->item_id,
                        qty:           abs($diff),
                        storeId:       $adjustment->store_id,
                        type:          'adjustment_sub',
                        variationKey:  $line->variation_key,
                        referenceType: InventoryAdjustment::class,
                        referenceId:   $adjustment->id,
                        note:          "Adjustment #{$adjustment->adjustment_number}"
                    );
                }
            }

            $adjustment->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        return redirect()->route('admin.inventory.adjustments.index')->with('success', 'Adjustment approved and stock updated.');
    }

    public function reject(Request $request, int $id)
    {
        $adjustment = InventoryAdjustment::findOrFail($id);
        $adjustment->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        return back()->with('success', 'Adjustment rejected.');
    }

    public function destroy(InventoryAdjustment $adjustment)
    {
        if ($adjustment->status !== 'draft') {
            return back()->with('error', 'Only draft adjustments can be deleted.');
        }
        $adjustment->delete();
        return redirect()->route('admin.inventory.adjustments.index')->with('success', 'Adjustment deleted.');
    }
}
