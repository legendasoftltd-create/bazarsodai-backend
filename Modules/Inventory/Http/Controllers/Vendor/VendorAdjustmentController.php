<?php

namespace Modules\Inventory\Http\Controllers\Vendor;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Modules\Inventory\Entities\InventoryAdjustment;
use Modules\Inventory\Entities\InventoryAdjustmentItem;

class VendorAdjustmentController extends Controller
{
    protected function storeId(): int
    {
        return Helpers::get_store_id();
    }

    public function index(Request $request)
    {
        $adjustments = InventoryAdjustment::where('store_id', $this->storeId())
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(15)->withQueryString();

        return view('inventory::vendor.adjustment.index', compact('adjustments'));
    }

    public function create()
    {
        $items = Item::where('store_id', $this->storeId())->where('status', 1)->get(['id', 'name', 'stock']);
        return view('inventory::vendor.adjustment.create', compact('items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'note'                   => 'nullable|string|max:1000',
            'items'                  => 'required|array|min:1',
            'items.*.item_id'        => ['required', \Illuminate\Validation\Rule::exists('items', 'id')->where('store_id', $this->storeId())],
            'items.*.physical_qty'   => 'required|numeric|min:0',
        ]);

        $storeId = $this->storeId();
        $store   = \App\Models\Store::findOrFail($storeId);

        DB::transaction(function () use ($request, $storeId, $store) {
            $adj = InventoryAdjustment::create([
                'adjustment_number' => InventoryAdjustment::generateAdjustmentNumber(),
                'store_id'          => $storeId,
                'module_id'         => $store->module_id,
                'status'            => 'pending_approval',
                'note'              => $request->note,
                'created_by'        => auth('vendor')->id() ?? auth('vendor_employee')->id(),
            ]);

            foreach ($request->items as $line) {
                $item = Item::where('store_id', $storeId)->findOrFail($line['item_id']);
                $systemQty  = $item->stock;
                $physicalQty = $line['physical_qty'];
                $diff       = $physicalQty - $systemQty;

                InventoryAdjustmentItem::create([
                    'adjustment_id' => $adj->id,
                    'item_id'       => $line['item_id'],
                    'variation_key' => $line['variation_key'] ?? null,
                    'system_qty'    => $systemQty,
                    'physical_qty'  => $physicalQty,
                    'difference'    => $diff,
                    'reason'        => $line['reason'] ?? null,
                ]);
            }
        });

        return redirect()->route('vendor.inventory.adjustments.index')->with('success', 'Adjustment submitted for approval.');
    }

    public function show(int $id)
    {
        $adjustment = InventoryAdjustment::with('adjustmentItems.item')
            ->where('store_id', $this->storeId())
            ->findOrFail($id);

        return view('inventory::vendor.adjustment.show', compact('adjustment'));
    }

    public function destroy(int $id)
    {
        $adj = InventoryAdjustment::where('store_id', $this->storeId())->findOrFail($id);

        if ($adj->status !== 'draft') {
            return back()->with('error', 'Only draft adjustments can be deleted.');
        }

        $adj->delete();
        return redirect()->route('vendor.inventory.adjustments.index')->with('success', 'Adjustment deleted.');
    }
}
