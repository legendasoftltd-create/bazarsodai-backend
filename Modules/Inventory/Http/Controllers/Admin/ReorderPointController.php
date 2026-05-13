<?php

namespace Modules\Inventory\Http\Controllers\Admin;

use App\Models\Item;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Inventory\Entities\ReorderPoint;

class ReorderPointController extends Controller
{
    public function index(Request $request)
    {
        $reorderPoints = ReorderPoint::with(['item', 'store'])
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->search,   fn($q) => $q->whereHas('item', fn($i) => $i->where('name', 'like', '%' . $request->search . '%')))
            ->latest()->paginate(20)->withQueryString();

        $stores = Store::active()->get(['id', 'name']);

        return view('inventory::admin.reorder.index', compact('reorderPoints', 'stores'));
    }

    public function create()
    {
        $stores = Store::active()->get(['id', 'name', 'module_id']);
        $items  = Item::active()->get(['id', 'name', 'store_id', 'stock']);
        return view('inventory::admin.reorder.create', compact('stores', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id'     => 'required|exists:items,id',
            'store_id'    => 'required|exists:stores,id',
            'reorder_at'  => 'required|numeric|min:0',
            'reorder_qty' => 'required|numeric|min:0',
        ]);

        $item = Item::findOrFail($request->item_id);

        ReorderPoint::updateOrCreate(
            [
                'item_id'       => $request->item_id,
                'store_id'      => $request->store_id,
                'variation_key' => $request->variation_key,
            ],
            [
                'module_id'   => $item->module_id,
                'reorder_at'  => $request->reorder_at,
                'reorder_qty' => $request->reorder_qty,
                'auto_notify' => $request->boolean('auto_notify', true),
            ]
        );

        return redirect()->route('admin.inventory.reorder-points.index')->with('success', 'Reorder point saved.');
    }

    public function edit(int $id)
    {
        $reorderPoint = ReorderPoint::with(['item', 'store'])->findOrFail($id);
        $stores = Store::active()->get(['id', 'name']);
        $items  = Item::where('store_id', $reorderPoint->store_id)->get(['id', 'name', 'stock']);
        return view('inventory::admin.reorder.edit', compact('reorderPoint', 'stores', 'items'));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'reorder_at'  => 'required|numeric|min:0',
            'reorder_qty' => 'required|numeric|min:0',
        ]);

        $rp = ReorderPoint::findOrFail($id);
        $rp->update([
            'reorder_at'  => $request->reorder_at,
            'reorder_qty' => $request->reorder_qty,
            'auto_notify' => $request->boolean('auto_notify', true),
        ]);

        return redirect()->route('admin.inventory.reorder-points.index')->with('success', 'Reorder point updated.');
    }

    public function destroy(int $id)
    {
        ReorderPoint::findOrFail($id)->delete();
        return back()->with('success', 'Reorder point deleted.');
    }
}
