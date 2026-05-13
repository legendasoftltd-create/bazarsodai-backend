<?php

namespace Modules\Inventory\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected $moduleNamespace = 'Modules\Inventory\Http\Controllers';

    public function boot()
    {
        parent::boot();
    }

    public function map()
    {
        $this->mapAdminRoutes();
        $this->mapVendorRoutes();
    }

    protected function mapAdminRoutes()
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Inventory', '/Routes/admin.php'));
    }

    protected function mapVendorRoutes()
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Inventory', '/Routes/vendor.php'));
    }
}
