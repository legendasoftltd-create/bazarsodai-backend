<?php

namespace Modules\Inventory\View\Composers;

use App\CentralLogics\Helpers;
use Illuminate\View\View;
use Modules\Inventory\Services\ReorderAlertService;

class DashboardComposer
{
    public function __construct(protected ReorderAlertService $alertService) {}

    public function composeAdmin(View $view): void
    {
        try {
            $view->with('inventoryLowStockCount', $this->alertService->countBelowReorderAll());
        } catch (\Throwable) {
            $view->with('inventoryLowStockCount', 0);
        }
    }

    public function composeVendor(View $view): void
    {
        try {
            $storeId = Helpers::get_store_id();
            $view->with('inventoryLowStockCount', $this->alertService->countBelowReorderForStore($storeId));
        } catch (\Throwable) {
            $view->with('inventoryLowStockCount', 0);
        }
    }
}
