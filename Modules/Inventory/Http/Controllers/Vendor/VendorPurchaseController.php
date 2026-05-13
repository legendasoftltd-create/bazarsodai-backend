<?php

namespace Modules\Inventory\Http\Controllers\Vendor;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseOrderItem;
use Modules\Inventory\Services\StockService;

class VendorPurchaseController extends Controller
{
    public function __construct(protected StockService $stock) {}

    protected function storeId(): int
    {
        return Helpers::get_store_id();
    }

    public function index(Request $request)
    {
        $storeId = $this->storeId();

        $orders = PurchaseOrder::with('supplier')
            ->where('store_id', $storeId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('po_number', 'like', '%' . $request->search . '%'))
            ->latest()->paginate(15)->withQueryString();

        return view('inventory::vendor.purchase.index', compact('orders'));
    }

    public function create()
    {
        $storeId   = $this->storeId();
        $suppliers = Supplier::where('store_id', $storeId)->active()->get();
        $items     = Item::where('store_id', $storeId)->where('status', 1)->get(['id', 'name', 'stock']);

        return view('inventory::vendor.purchase.create', compact('suppliers', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'       => 'nullable|exists:suppliers,id',
            'expected_at'       => 'nullable|date',
            'note'              => 'nullable|string|max:1000',
            'items'             => 'required|array|min:1',
            'items.*.item_id'   => ['required', Rule::exists('items', 'id')->where('store_id', $this->storeId())],
            'items.*.qty'       => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $storeId = $this->storeId();

        DB::transaction(function () use ($request, $storeId) {
            $store = \App\Models\Store::findOrFail($storeId);
            $po    = PurchaseOrder::create([
                'po_number'   => PurchaseOrder::generatePoNumber(),
                'supplier_id' => $request->supplier_id,
                'store_id'    => $storeId,
                'module_id'   => $store->module_id,
                'status'      => 'ordered',
                'ordered_at'  => now(),
                'expected_at' => $request->expected_at,
                'note'        => $request->note,
                'created_by'  => auth('vendor')->id() ?? auth('vendor_employee')->id(),
            ]);

            $totalQty = $totalCost = 0;

            foreach ($request->items as $line) {
                $lineTotal = $line['qty'] * $line['unit_cost'];
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'item_id'           => $line['item_id'],
                    'variation_key'     => $line['variation_key'] ?? null,
                    'qty_ordered'       => $line['qty'],
                    'unit_cost'         => $line['unit_cost'],
                    'total_cost'        => $lineTotal,
                ]);
                $totalQty  += $line['qty'];
                $totalCost += $lineTotal;
            }

            $po->update(['total_qty' => $totalQty, 'sub_total' => $totalCost, 'total_cost' => $totalCost]);
        });

        return redirect()->route('vendor.inventory.purchases.index')->with('success', 'Purchase order created.');
    }

    public function show(int $id)
    {
        $po = PurchaseOrder::with(['supplier', 'items.item'])
            ->where('store_id', $this->storeId())
            ->findOrFail($id);

        return view('inventory::vendor.purchase.show', compact('po'));
    }

    public function update(Request $request, int $id)
    {
        $po = PurchaseOrder::where('store_id', $this->storeId())->findOrFail($id);

        if (!in_array($po->status, ['draft', 'ordered'])) {
            return back()->with('error', 'Cannot edit a received or cancelled PO.');
        }

        $po->update($request->only(['supplier_id', 'expected_at', 'note']));
        return back()->with('success', 'Purchase order updated.');
    }

    public function receive(Request $request, int $id)
    {
        $po = PurchaseOrder::with('items.item')
            ->where('store_id', $this->storeId())
            ->findOrFail($id);

        if ($po->status === 'received') {
            return back()->with('error', 'This PO is already fully received.');
        }

        DB::transaction(function () use ($po, $request) {
            foreach ($po->items as $line) {
                $receivedQty = $request->input("received.{$line->id}", $line->qty_ordered);
                if ($receivedQty <= 0) continue;

                $batchNumber = $request->input("batch.{$line->id}");
                $expiresAt   = $request->input("expires_at.{$line->id}");

                $movement = $this->stock->add(
                    itemId:        $line->item_id,
                    qty:           $receivedQty,
                    storeId:       $po->store_id,
                    type:          'purchase',
                    unitCost:      $line->unit_cost,
                    variationKey:  $line->variation_key,
                    referenceType: PurchaseOrder::class,
                    referenceId:   $po->id,
                    note:          "PO #{$po->po_number}",
                    batchNumber:   $batchNumber,
                    expiresAt:     $expiresAt ? new \DateTime($expiresAt) : null
                );

                $line->update([
                    'qty_received' => $line->qty_received + $receivedQty,
                    'batch_id'     => $movement->batch_id,
                ]);
            }

            $po->update(['status' => 'received', 'received_at' => now()]);
        });

        return redirect()->route('vendor.inventory.purchases.index')->with('success', 'Stock received successfully.');
    }

    public function purchaseReturn(Request $request, int $id)
    {
        $request->validate([
            'item_id' => ['required', Rule::exists('items', 'id')->where('store_id', $this->storeId())],
            'qty'     => 'required|numeric|min:0.01',
            'note'    => 'nullable|string',
        ]);

        $po = PurchaseOrder::where('store_id', $this->storeId())->findOrFail($id);

        $this->stock->deduct(
            itemId:        $request->item_id,
            qty:           $request->qty,
            storeId:       $po->store_id,
            type:          'purchase_return',
            referenceType: PurchaseOrder::class,
            referenceId:   $po->id,
            note:          $request->note ?? "Purchase return — PO #{$po->po_number}"
        );

        return back()->with('success', 'Purchase return recorded.');
    }

    public function destroy(int $id)
    {
        $po = PurchaseOrder::where('store_id', $this->storeId())->findOrFail($id);

        if ($po->status !== 'draft') {
            return back()->with('error', 'Only draft POs can be deleted.');
        }

        $po->delete();
        return redirect()->route('vendor.inventory.purchases.index')->with('success', 'PO deleted.');
    }
}
