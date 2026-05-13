<?php

namespace Modules\Inventory\Services;

use App\Models\Item;
use App\Models\Store;
use Illuminate\Support\Facades\Notification;
use Modules\Inventory\Entities\ReorderPoint;
use Modules\Inventory\Notifications\LowStockNotification;

class ReorderAlertService
{
    /**
     * Send notification for a single triggered reorder point.
     */
    public function notifyLowStock(ReorderPoint $reorder, float $currentStock): void
    {
        if (!$reorder->auto_notify) return;

        $store = Store::find($reorder->store_id);
        if (!$store) return;

        $emails = array_filter([
            optional($store->vendor)->email,
            config('inventory.alert_email'),
        ]);

        if (empty($emails)) return;

        $item = Item::find($reorder->item_id);
        if (!$item) return;

        Notification::route('mail', $emails)
            ->notify(new LowStockNotification($item, $store, $reorder, $currentStock));
    }

    /**
     * Scan all active reorder points and fire alerts for items below threshold.
     * Used by the scheduled queue job.
     */
    public function scanAll(): int
    {
        $count = 0;

        ReorderPoint::where('auto_notify', 1)
            ->with(['item', 'store'])
            ->chunkById(100, function ($points) use (&$count) {
                foreach ($points as $rp) {
                    if (!$rp->item) continue;
                    $currentStock = (float) $rp->item->stock;
                    if ($currentStock <= $rp->reorder_at) {
                        $this->notifyLowStock($rp, $currentStock);
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * Count items below reorder point for a given store (dashboard widget).
     */
    public function countBelowReorderForStore(int $storeId): int
    {
        return ReorderPoint::where('store_id', $storeId)
            ->with('item:id,stock')
            ->get()
            ->filter(fn($rp) => $rp->item && $rp->item->stock <= $rp->reorder_at)
            ->count();
    }

    /**
     * Count items below reorder point across all stores (admin widget).
     */
    public function countBelowReorderAll(): int
    {
        return ReorderPoint::with('item:id,stock')
            ->get()
            ->filter(fn($rp) => $rp->item && $rp->item->stock <= $rp->reorder_at)
            ->count();
    }
}
