<?php

namespace Modules\Accounts\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Accounts\Console\Commands\BackfillAccountsCommand;
use Modules\Accounts\Console\Commands\ParallelCheckCommand;
use Modules\Accounts\Console\Commands\ReconcileCommand;
use Modules\Accounts\Events\AccountingEventOccurred;
use Modules\Accounts\Listeners\PostJournalEntry;
use Modules\Accounts\Services\AccountingService;
use Modules\Accounts\Services\BackfillService;
use Modules\Accounts\Services\ReconcileService;

class AccountsServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Accounts';
    protected $moduleNameLower = 'accounts';

    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        Event::listen(AccountingEventOccurred::class, PostJournalEntry::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                BackfillAccountsCommand::class,
                ReconcileCommand::class,
                ParallelCheckCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(AccountingService::class);
        $this->app->singleton(BackfillService::class);
        $this->app->singleton(ReconcileService::class);
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
