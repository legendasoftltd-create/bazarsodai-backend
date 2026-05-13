<?php

namespace Modules\Inventory\Observers;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Services\StockService;

class OrderObserver
{
    public function __construct(protected StockService $stock) {}

    /**
     * Deduct stock when an order is confirmed.
     */
    public function updated(Order $order): void
    {
        $original = $order->getOriginal('order_status');
        $new      = $order->order_status;

        // Deduct on confirmed
        if ($original !== 'confirmed' && $new === 'confirmed') {
            $this->deductStock($order);
        }

        // Restore on cancelled (before confirmed = no stock was deducted)
        if (in_array($original, ['confirmed', 'processing', 'handover'])
            && $new === 'canceled') {
            $this->restoreStock($order, 'sale_return', 'Order cancelled');
        }

        // Restore on refunded
        if ($original !== 'refunded' && $new === 'refunded') {
            $this->restoreStock($order, 'sale_return', 'Order refunded');
        }
    }

    protected function deductStock(Order $order): void
    {
        if (!$order->module || !config('module.' . $order->module->module_type . '.stock', false)) {
            return;
        }

        foreach ($order->details as $detail) {
            try {
                $variant  = $this->getVariantKey($detail);
                $movement = $this->stock->deduct(
                    itemId:        $detail->item_id,
                    qty:           $detail->quantity,
                    storeId:       $order->store_id,
                    type:          'sale',
                    variationKey:  $variant,
                    referenceType: Order::class,
                    referenceId:   $order->id,
                    note:          "Order #{$order->id}"
                );

                // Save cost snapshot on order detail
                $detail->unit_cost_at_sale = $movement->unit_cost;
                $detail->batch_id          = $movement->batch_id;
                $detail->saveQuietly();

            } catch (\Throwable $e) {
                Log::error("Inventory: failed to deduct stock for order {$order->id}, item {$detail->item_id}: {$e->getMessage()}");
            }
        }
    }

    protected function restoreStock(Order $order, string $type, string $note): void
    {
        if (!$order->module || !config('module.' . $order->module->module_type . '.stock', false)) {
            return;
        }

        foreach ($order->details as $detail) {
            try {
                $variant = $this->getVariantKey($detail);
                $this->stock->add(
                    itemId:        $detail->item_id,
                    qty:           $detail->quantity,
                    storeId:       $order->store_id,
                    type:          $type,
                    unitCost:      $detail->unit_cost_at_sale ?? 0,
                    variationKey:  $variant,
                    referenceType: Order::class,
                    referenceId:   $order->id,
                    note:          "{$note} — Order #{$order->id}"
                );
            } catch (\Throwable $e) {
                Log::error("Inventory: failed to restore stock for order {$order->id}, item {$detail->item_id}: {$e->getMessage()}");
            }
        }
    }

    protected function getVariantKey(OrderDetail $detail): ?string
    {
        if (empty($detail->variation)) return null;
        $v = is_array($detail->variation) ? $detail->variation : json_decode($detail->variation, true);
        return $v[0]['type'] ?? null;
    }
}
