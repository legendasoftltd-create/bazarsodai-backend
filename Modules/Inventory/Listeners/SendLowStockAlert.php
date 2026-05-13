<?php

namespace Modules\Inventory\Listeners;

use Modules\Inventory\Events\LowStockDetected;
use Modules\Inventory\Services\ReorderAlertService;

class SendLowStockAlert
{
    public function __construct(protected ReorderAlertService $alertService) {}

    public function handle(LowStockDetected $event): void
    {
        $this->alertService->notifyLowStock($event->reorderPoint, $event->currentStock);
    }
}
