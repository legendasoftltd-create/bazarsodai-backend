<?php

namespace Modules\Inventory\Http\Controllers\Vendor;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Modules\Inventory\Entities\Supplier;

class VendorSupplierController extends Controller
{
    protected function storeId(): int
    {
        return Helpers::get_store_id();
    }

    public function index(Request $request)
    {
        $suppliers = Supplier::where('store_id', $this->storeId())
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->latest()->paginate(15)->withQueryString();

        return view('inventory::vendor.supplier.index', compact('suppliers'));
    }

    public function create()
    {
        return view('inventory::vendor.supplier.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        Supplier::create([
            'name'       => $request->name,
            'phone'      => $request->phone,
            'email'      => $request->email,
            'address'    => $request->address,
            'store_id'   => $this->storeId(),
            'created_by' => auth('vendor')->id() ?? auth('vendor_employee')->id(),
        ]);

        return redirect()->route('vendor.inventory.suppliers.index')->with('success', 'Supplier added successfully.');
    }

    public function edit(Supplier $supplier)
    {
        abort_if($supplier->store_id !== $this->storeId(), 403);
        return view('inventory::vendor.supplier.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        abort_if($supplier->store_id !== $this->storeId(), 403);
        $request->validate(['name' => 'required|string|max:255']);
        $supplier->update($request->only(['name', 'phone', 'email', 'address', 'status']));

        return redirect()->route('vendor.inventory.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        abort_if($supplier->store_id !== $this->storeId(), 403);
        $supplier->delete();
        return back()->with('success', 'Supplier deleted.');
    }
}
