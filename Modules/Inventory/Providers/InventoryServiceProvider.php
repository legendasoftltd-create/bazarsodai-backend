<?php

namespace Modules\Inventory\Providers;

use App\Models\Order;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Jobs\CheckReorderPointJob;
use Modules\Inventory\Jobs\ExpiryAlertJob;
use Modules\Inventory\Jobs\ReleaseExpiredReservationsJob;
use Modules\Inventory\Events\LowStockDetected;
use Modules\Inventory\Listeners\SendLowStockAlert;
use Modules\Inventory\Observers\OrderObserver;
use Modules\Inventory\View\Composers\DashboardComposer;

class InventoryServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Inventory';
    protected $moduleNameLower = 'inventory';

    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        Order::observe(OrderObserver::class);

        Event::listen(LowStockDetected::class, SendLowStockAlert::class);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->job(new CheckReorderPointJob)->dailyAt('08:00')->name('inventory:check-reorder');
            $schedule->job(new ExpiryAlertJob)->dailyAt('09:00')->name('inventory:expiry-alert');
            $schedule->job(new ReleaseExpiredReservationsJob)->everyFiveMinutes()->name('inventory:release-reservations');
        });

        $composer = $this->app->make(DashboardComposer::class);

        View::composer('admin-views.dashboard', fn($v) => $composer->composeAdmin($v));
        View::composer('admin-views.dashboard-food', fn($v) => $composer->composeAdmin($v));
        View::composer('admin-views.dashboard-grocery', fn($v) => $composer->composeAdmin($v));
        View::composer('admin-views.dashboard-ecommerce', fn($v) => $composer->composeAdmin($v));
        View::composer('admin-views.dashboard-pharmacy', fn($v) => $composer->composeAdmin($v));
        View::composer('vendor-views.dashboard', fn($v) => $composer->composeVendor($v));
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    public function registerViews()
    {
        $viewPath   = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
