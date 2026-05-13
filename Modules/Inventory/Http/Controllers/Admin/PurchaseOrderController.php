<?php

namespace Modules\Inventory\Http\Controllers\Admin;

use App\Models\Item;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\StockBatch;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseOrderItem;
use Modules\Inventory\Services\StockService;

class PurchaseOrderController extends Controller
{
    public function __construct(protected StockService $stock) {}

    public function index(Request $request)
    {
        $orders = PurchaseOrder::with(['supplier', 'store'])
            ->when($request->store_id,   fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->status,     fn($q) => $q->where('status', $request->status))
            ->when($request->search,     fn($q) => $q->where('po_number', 'like', '%' . $request->search . '%'))
            ->latest()->paginate(20)->withQueryString();

        $stores    = Store::active()->get();
        $suppliers = Supplier::active()->get();

        return view('inventory::admin.purchase.index', compact('orders', 'stores', 'suppliers'));
    }

    public function create()
    {
        $stores    = Store::active()->get();
        $suppliers = Supplier::active()->get();
        $items     = Item::active()->get(['id', 'name', 'store_id', 'module_id', 'stock']);

        return view('inventory::admin.purchase.create', compact('stores', 'suppliers', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id'    => 'required|exists:stores,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'items'       => 'required|array|min:1',
            'items.*.item_id'   => 'required|exists:items,id',
            'items.*.qty'       => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $store = Store::findOrFail($request->store_id);
            $po    = PurchaseOrder::create([
                'po_number'   => PurchaseOrder::generatePoNumber(),
                'supplier_id' => $request->supplier_id,
                'store_id'    => $request->store_id,
                'module_id'   => $store->module_id,
                'status'      => 'ordered',
                'ordered_at'  => now(),
                'expected_at' => $request->expected_at,
                'note'        => $request->note,
                'created_by'  => auth()->id(),
            ]);

            $totalQty  = 0;
            $totalCost = 0;

            foreach ($request->items as $line) {
                Item::where('store_id', $request->store_id)->findOrFail($line['item_id']);
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

        return redirect()->route('admin.inventory.purchases.index')->with('success', 'Purchase order created.');
    }

    public function show(PurchaseOrder $purchase)
    {
        $purchase->load(['supplier', 'store', 'items.item']);
        return view('inventory::admin.purchase.show', compact('purchase'));
    }

    public function update(Request $request, PurchaseOrder $purchase)
    {
        if (!in_array($purchase->status, ['draft', 'ordered'])) {
            return back()->with('error', 'Cannot edit a received or cancelled PO.');
        }
        $purchase->update($request->only(['supplier_id', 'expected_at', 'note']));
        return back()->with('success', 'Purchase order updated.');
    }

    /** Mark PO as received — add stock */
    public function receive(Request $request, int $id)
    {
        $po = PurchaseOrder::with('items.item')->findOrFail($id);

        if ($po->status === 'received') {
            return back()->with('error', 'This PO is already fully received.');
        }

        DB::transaction(function () use ($po, $request) {
            foreach ($po->items as $line) {
                $receivedQty = $request->input("received.{$line->id}", $line->qty_ordered);
                if ($receivedQty <= 0) continue;

                // Create batch for FIFO/LIFO
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

            $po->update([
                'status'      => 'received',
                'received_at' => now(),
            ]);
        });

        return redirect()->route('admin.inventory.purchases.index')->with('success', 'Stock received successfully.');
    }

    /** Purchase return — reduce stock */
    public function purchaseReturn(Request $request, int $id)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'qty'     => 'required|numeric|min:0.01',
            'note'    => 'nullable|string',
        ]);

        $po   = PurchaseOrder::findOrFail($id);
        $item = Item::where('store_id', $po->store_id)->findOrFail($request->item_id);

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

    public function destroy(PurchaseOrder $purchase)
    {
        if ($purchase->status !== 'draft') {
            return back()->with('error', 'Only draft POs can be deleted.');
        }
        $purchase->delete();
        return redirect()->route('admin.inventory.purchases.index')->with('success', 'PO deleted.');
    }
}
