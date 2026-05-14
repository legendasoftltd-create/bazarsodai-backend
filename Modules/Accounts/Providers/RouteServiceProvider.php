<?php

namespace Modules\Accounts\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected $moduleNamespace = 'Modules\Accounts\Http\Controllers';

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
            ->group(module_path('Accounts', '/Routes/admin.php'));
    }

    protected function mapVendorRoutes()
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Accounts', '/Routes/vendor.php'));
    }
}
