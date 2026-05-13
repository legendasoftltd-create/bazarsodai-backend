<?php

namespace Modules\Inventory\Services;

use App\Models\Item;
use Modules\Inventory\Entities\StockBatch;

class ValuationService
{
    /**
     * Deduct stock using FIFO — oldest batch first.
     * Returns array of ['batch_id', 'qty', 'unit_cost'] consumed.
     */
    public function fifoDeduct(int $itemId, int $storeId, float $qty, ?string $variationKey = null): array
    {
        $consumed = [];
        $remaining = $qty;

        $batches = StockBatch::where('item_id', $itemId)
            ->where('store_id', $storeId)
            ->when($variationKey, fn($q) => $q->where('variation_key', $variationKey))
            ->where('qty_remaining', '>', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $take = min($batch->qty_remaining, $remaining);
            $batch->qty_remaining -= $take;
            $batch->save();

            $consumed[] = [
                'batch_id'  => $batch->id,
                'qty'       => $take,
                'unit_cost' => $batch->unit_cost,
            ];

            $remaining -= $take;
        }

        return $consumed;
    }

    /**
     * Deduct stock using LIFO — newest batch first.
     */
    public function lifoDeduct(int $itemId, int $storeId, float $qty, ?string $variationKey = null): array
    {
        $consumed = [];
        $remaining = $qty;

        $batches = StockBatch::where('item_id', $itemId)
            ->where('store_id', $storeId)
            ->when($variationKey, fn($q) => $q->where('variation_key', $variationKey))
            ->where('qty_remaining', '>', 0)
            ->orderBy('created_at', 'desc')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $take = min($batch->qty_remaining, $remaining);
            $batch->qty_remaining -= $take;
            $batch->save();

            $consumed[] = [
                'batch_id'  => $batch->id,
                'qty'       => $take,
                'unit_cost' => $batch->unit_cost,
            ];

            $remaining -= $take;
        }

        return $consumed;
    }

    /**
     * Recalculate weighted average cost after a new purchase.
     */
    public function recalculateAverage(Item $item, float $newQty, float $newUnitCost): float
    {
        $existingValue = $item->stock * $item->average_cost;
        $newValue      = $newQty * $newUnitCost;
        $totalQty      = $item->stock + $newQty;

        if ($totalQty <= 0) return 0;

        return round(($existingValue + $newValue) / $totalQty, 4);
    }

    /**
     * Get the valuation method for an item (item override or store default).
     */
    public function getMethod(Item $item): string
    {
        if (!empty($item->valuation_method)) {
            return $item->valuation_method;
        }

        $storeConfig = $item->store?->config;
        if ($storeConfig && !empty($storeConfig->inventory_valuation_method)) {
            return $storeConfig->inventory_valuation_method;
        }

        return config('inventory.default_valuation_method', 'average');
    }

    /**
     * Calculate total stock value for an item using its valuation method.
     */
    public function calculateStockValue(Item $item): float
    {
        $method = $this->getMethod($item);

        if ($method === 'average') {
            return round($item->stock * $item->average_cost, 2);
        }

        // FIFO / LIFO — sum batch values
        return StockBatch::where('item_id', $item->id)
            ->where('store_id', $item->store_id)
            ->where('qty_remaining', '>', 0)
            ->selectRaw('SUM(qty_remaining * unit_cost) as total_value')
            ->value('total_value') ?? 0.0;
    }
}
