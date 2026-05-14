<?php

namespace Modules\Inventory\Services;

use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Inventory\Entities\StockBatch;
use Modules\Inventory\Entities\StockMovement;
use Modules\Inventory\Entities\ReorderPoint;

class StockService
{
    public function __construct(protected ValuationService $valuation) {}

    /**
     * Add stock (purchase, opening, return, transfer_in, adjustment_add).
     */
    public function add(
        int $itemId,
        float $qty,
        int $storeId,
        string $type,
        float $unitCost = 0,
        ?string $variationKey = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
        ?string $batchNumber = null,
        ?\DateTimeInterface $expiresAt = null
    ): StockMovement {
        return DB::transaction(function () use (
            $itemId, $qty, $storeId, $type, $unitCost,
            $variationKey, $referenceType, $referenceId, $note, $batchNumber, $expiresAt
        ) {
            $item = Item::lockForUpdate()->findOrFail($itemId);
            $method = $this->valuation->getMethod($item);

            $stockBefore = $this->getCurrentStock($itemId, $storeId, $variationKey);
            $stockAfter  = $stockBefore + $qty;

            $batchId = null;

            if (in_array($method, ['fifo', 'lifo'])) {
                $batch = StockBatch::create([
                    'item_id'          => $itemId,
                    'store_id'         => $storeId,
                    'variation_key'    => $variationKey,
                    'batch_number'     => $batchNumber,
                    'qty_initial'      => $qty,
                    'qty_remaining'    => $qty,
                    'unit_cost'        => $unitCost,
                    'valuation_method' => $method,
                    'expires_at'       => $expiresAt,
                ]);
                $batchId = $batch->id;
            }

            if ($method === 'average') {
                $newAvg = $this->valuation->recalculateAverage($item, $qty, $unitCost);
                $item->average_cost = $newAvg;
            }

            // Update item stock
            $item->stock += $qty;
            if ($variationKey) {
                $item->variations = $this->adjustVariationStock($item->variations, $variationKey, $qty);
            }
            $item->total_stock_value = $this->valuation->calculateStockValue($item);
            $item->save();

            return StockMovement::create([
                'item_id'          => $itemId,
                'variation_key'    => $variationKey,
                'store_id'         => $storeId,
                'module_id'        => $item->module_id,
                'type'             => $type,
                'qty_in'           => $qty,
                'qty_out'          => 0,
                'stock_before'     => $stockBefore,
                'stock_after'      => $stockAfter,
                'valuation_method' => $method,
                'unit_cost'        => $unitCost,
                'total_cost'       => $qty * $unitCost,
                'batch_id'         => $batchId,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'note'             => $note,
                'created_by'       => Auth::id(),
            ]);
        });

        // ── Accounting hook (outside transaction to avoid rollback on failure) ──
        if ($movement->total_cost > 0 && in_array($type, ['purchase', 'opening', 'transfer_in', 'adjustment_add'])) {
            try {
                app(\Modules\Accounts\Services\AccountingService::class)->post(
                    'stock_received',
                    ['total_cost' => $movement->total_cost],
                    [
                        'reference_type' => $referenceType ?? 'StockMovement',
                        'reference_id'   => $movement->id,
                        'store_id'       => $storeId,
                    ]
                );
            } catch (\Exception $e) {
                info('Accounting[stock_received] StockMovement#' . $movement->id . ': ' . $e->getMessage());
            }
        }

        return $movement;
    }

    /**
     * Deduct stock (sale, damaged, broken, transfer_out, adjustment_sub, internal_use).
     */
    public function deduct(
        int $itemId,
        float $qty,
        int $storeId,
        string $type,
        ?string $variationKey = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null
    ): StockMovement {
        return DB::transaction(function () use (
            $itemId, $qty, $storeId, $type,
            $variationKey, $referenceType, $referenceId, $note
        ) {
            $item = Item::lockForUpdate()->findOrFail($itemId);
            $method = $this->valuation->getMethod($item);

            $stockBefore = $this->getCurrentStock($itemId, $storeId, $variationKey);
            $stockAfter  = max(0, $stockBefore - $qty);

            $unitCost = 0;
            $batchId  = null;

            if ($method === 'fifo') {
                $consumed = $this->valuation->fifoDeduct($itemId, $storeId, $qty, $variationKey);
                $unitCost = $this->weightedCost($consumed);
                $batchId  = count($consumed) === 1 ? $consumed[0]['batch_id'] : null;
            } elseif ($method === 'lifo') {
                $consumed = $this->valuation->lifoDeduct($itemId, $storeId, $qty, $variationKey);
                $unitCost = $this->weightedCost($consumed);
                $batchId  = count($consumed) === 1 ? $consumed[0]['batch_id'] : null;
            } else {
                $unitCost = $item->average_cost;
            }

            $item->stock = max(0, $item->stock - $qty);
            if ($variationKey) {
                $item->variations = $this->adjustVariationStock($item->variations, $variationKey, -$qty);
            }
            $item->total_stock_value = $this->valuation->calculateStockValue($item);
            $item->save();

            $movement = StockMovement::create([
                'item_id'          => $itemId,
                'variation_key'    => $variationKey,
                'store_id'         => $storeId,
                'module_id'        => $item->module_id,
                'type'             => $type,
                'qty_in'           => 0,
                'qty_out'          => $qty,
                'stock_before'     => $stockBefore,
                'stock_after'      => $stockAfter,
                'valuation_method' => $method,
                'unit_cost'        => $unitCost,
                'total_cost'       => $qty * $unitCost,
                'batch_id'         => $batchId,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'note'             => $note,
                'created_by'       => Auth::id(),
            ]);

            $this->checkReorderPoint($itemId, $storeId, $variationKey, $stockAfter);

            return $movement;
        });

        // ── Accounting hook ───────────────────────────────────────────────────
        if ($movement->total_cost > 0 && in_array($type, ['sale', 'damaged', 'broken', 'internal_use', 'adjustment_sub', 'transfer_out'])) {
            try {
                app(\Modules\Accounts\Services\AccountingService::class)->post(
                    'stock_deducted',
                    ['total_cost' => $movement->total_cost],
                    [
                        'reference_type' => $referenceType ?? 'StockMovement',
                        'reference_id'   => $movement->id,
                        'store_id'       => $storeId,
                    ]
                );
            } catch (\Exception $e) {
                info('Accounting[stock_deducted] StockMovement#' . $movement->id . ': ' . $e->getMessage());
            }
        }

        return $movement;
    }

    /**
     * Reserve stock when item is added to cart.
     */
    public function reserve(int $itemId, int $storeId, float $qty, int $cartId, ?string $variationKey = null): bool
    {
        return DB::transaction(function () use ($itemId, $storeId, $qty, $cartId, $variationKey) {
            $available = $this->getCurrentStock($itemId, $storeId, $variationKey);
            if ($available < $qty) return false;

            $minutes = config('inventory.stock_reservation_minutes', 15);

            \App\Models\Cart::where('id', $cartId)->update([
                'stock_reserved'  => 1,
                'reserved_until'  => now()->addMinutes($minutes),
            ]);

            return true;
        });
    }

    /**
     * Release cart stock reservation (on expiry or checkout).
     */
    public function releaseReservation(int $cartId): void
    {
        \App\Models\Cart::where('id', $cartId)->update([
            'stock_reserved' => 0,
            'reserved_until' => null,
        ]);
    }

    /**
     * Get current available stock (total - active cart reservations).
     */
    public function getCurrentStock(int $itemId, int $storeId, ?string $variationKey = null): float
    {
        $item = Item::find($itemId);
        if (!$item) return 0;

        if ($variationKey) {
            $variations = is_array($item->variations)
                ? $item->variations
                : json_decode($item->variations, true);

            foreach ($variations ?? [] as $v) {
                if (($v['type'] ?? '') === $variationKey) {
                    return (float) ($v['stock'] ?? 0);
                }
            }
            return 0;
        }

        $totalStock = (float) $item->stock;

        $reserved = \App\Models\Cart::where('item_id', $itemId)
            ->where('stock_reserved', 1)
            ->where('reserved_until', '>', now())
            ->sum('quantity');

        return max(0, $totalStock - $reserved);
    }

    /**
     * Check if stock has fallen below reorder point and fire notification.
     */
    protected function checkReorderPoint(int $itemId, int $storeId, ?string $variationKey, float $currentStock): void
    {
        $reorder = ReorderPoint::where('item_id', $itemId)
            ->where('store_id', $storeId)
            ->where('auto_notify', 1)
            ->when($variationKey, fn($q) => $q->where('variation_key', $variationKey))
            ->first();

        if ($reorder && $currentStock <= $reorder->reorder_at) {
            // Fire low-stock notification (Phase 3)
            event(new \Modules\Inventory\Events\LowStockDetected($reorder, $currentStock));
        }
    }

    /**
     * Adjust variation stock in JSON field.
     */
    protected function adjustVariationStock(mixed $variations, string $key, float $delta): string
    {
        $vars = is_array($variations) ? $variations : json_decode($variations, true);
        foreach ($vars as &$v) {
            if (($v['type'] ?? '') === $key) {
                $v['stock'] = max(0, ($v['stock'] ?? 0) + $delta);
            }
        }
        return json_encode($vars);
    }

    /**
     * Calculate weighted average unit cost from FIFO/LIFO consumed batches.
     */
    protected function weightedCost(array $consumed): float
    {
        $totalQty  = array_sum(array_column($consumed, 'qty'));
        $totalCost = array_sum(array_map(fn($c) => $c['qty'] * $c['unit_cost'], $consumed));
        return $totalQty > 0 ? round($totalCost / $totalQty, 4) : 0;
    }
}
