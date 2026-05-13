<?php

namespace Modules\Inventory\Http\Controllers\Admin;

use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Inventory\Entities\Supplier;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::with('store')
            ->when($request->search,   fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->latest()->paginate(20)->withQueryString();

        $stores = Store::active()->get(['id', 'name']);

        return view('inventory::admin.supplier.index', compact('suppliers', 'stores'));
    }

    public function create()
    {
        $stores = Store::active()->get(['id', 'name', 'module_id']);
        return view('inventory::admin.supplier.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        Supplier::create($request->only(['name', 'phone', 'email', 'address', 'store_id', 'module_id']) + ['created_by' => auth()->id()]);

        return redirect()->route('admin.inventory.suppliers.index')->with('success', 'Supplier created.');
    }

    public function edit(Supplier $supplier)
    {
        $stores = Store::active()->get(['id', 'name', 'module_id']);
        return view('inventory::admin.supplier.edit', compact('supplier', 'stores'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $supplier->update($request->only(['name', 'phone', 'email', 'address', 'status']));

        return redirect()->route('admin.inventory.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return back()->with('success', 'Supplier deleted.');
    }
}
