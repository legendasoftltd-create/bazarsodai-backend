<?php

namespace Modules\Inventory\Events;

use Modules\Inventory\Entities\ReorderPoint;
use Illuminate\Foundation\Events\Dispatchable;

class LowStockDetected
{
    use Dispatchable;

    public function __construct(
        public readonly ReorderPoint $reorderPoint,
        public readonly float $currentStock
    ) {}
}
