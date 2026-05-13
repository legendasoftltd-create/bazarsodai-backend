<?php

namespace Modules\Inventory\Http\Controllers\Admin;

use App\Models\Item;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Inventory\Entities\StockTransfer;
use Modules\Inventory\Entities\StockTransferItem;
use Modules\Inventory\Services\StockService;

class StockTransferController extends Controller
{
    public function __construct(protected StockService $stock) {}

    public function index(Request $request)
    {
        $transfers = StockTransfer::with(['fromStore', 'toStore'])
            ->when($request->store_id, fn($q) => $q->where('from_store_id', $request->store_id)->orWhere('to_store_id', $request->store_id))
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(20)->withQueryString();

        $stores = Store::active()->get();
        return view('inventory::admin.transfer.index', compact('transfers', 'stores'));
    }

    public function create()
    {
        $stores = Store::active()->get();
        $items  = Item::active()->get(['id', 'name', 'store_id', 'stock']);
        return view('inventory::admin.transfer.create', compact('stores', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id'   => 'required|exists:stores,id|different:from_store_id',
            'items'         => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.qty'     => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request) {
            $fromStore = Store::findOrFail($request->from_store_id);
            $transfer  = StockTransfer::create([
                'transfer_number' => StockTransfer::generateTransferNumber(),
                'from_store_id'   => $request->from_store_id,
                'to_store_id'     => $request->to_store_id,
                'module_id'       => $fromStore->module_id,
                'status'          => 'pending',
                'note'            => $request->note,
                'created_by'      => auth()->id(),
            ]);

            foreach ($request->items as $line) {
                Item::where('store_id', $request->from_store_id)->findOrFail($line['item_id']);
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'item_id'           => $line['item_id'],
                    'variation_key'     => $line['variation_key'] ?? null,
                    'qty_requested'     => $line['qty'],
                    'qty_transferred'   => $line['qty'],
                ]);

                // Deduct from source store immediately
                $this->stock->deduct(
                    itemId:        $line['item_id'],
                    qty:           $line['qty'],
                    storeId:       $request->from_store_id,
                    type:          'transfer_out',
                    variationKey:  $line['variation_key'] ?? null,
                    referenceType: StockTransfer::class,
                    referenceId:   $transfer->id,
                    note:          "Transfer #{$transfer->transfer_number}"
                );
            }

            $transfer->update(['status' => 'in_transit', 'transferred_at' => now()]);
        });

        return redirect()->route('admin.inventory.transfers.index')->with('success', 'Stock transfer initiated.');
    }

    public function show(StockTransfer $transfer)
    {
        $transfer->load(['fromStore', 'toStore', 'items.item']);
        return view('inventory::admin.transfer.show', compact('transfer'));
    }

    /** Mark transfer as received — add stock to destination */
    public function receive(Request $request, int $id)
    {
        $transfer = StockTransfer::with('items.item')->findOrFail($id);

        if ($transfer->status === 'received') {
            return back()->with('error', 'Already received.');
        }

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $line) {
                $this->stock->add(
                    itemId:        $line->item_id,
                    qty:           $line->qty_transferred,
                    storeId:       $transfer->to_store_id,
                    type:          'transfer_in',
                    variationKey:  $line->variation_key,
                    referenceType: StockTransfer::class,
                    referenceId:   $transfer->id,
                    note:          "Transfer #{$transfer->transfer_number}"
                );
                $line->update(['qty_received' => $line->qty_transferred]);
            }
            $transfer->update(['status' => 'received', 'received_at' => now()]);
        });

        return redirect()->route('admin.inventory.transfers.index')->with('success', 'Transfer received.');
    }

    public function destroy(StockTransfer $transfer)
    {
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'Cannot delete a transfer already in transit.');
        }
        $transfer->delete();
        return redirect()->route('admin.inventory.transfers.index')->with('success', 'Transfer deleted.');
    }
}
