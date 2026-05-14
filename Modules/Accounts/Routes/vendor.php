<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounts\Http\Controllers\Vendor\VendorAccountsController;

// Vendor panel prefix is 'vendor-panel' (set in app/Providers/RouteServiceProvider.php)
Route::group([
    'prefix'     => 'vendor-panel/accounts',
    'as'         => 'vendor.accounts.',
    'middleware' => ['web', 'vendor'],
], function () {
    Route::get('/',          [VendorAccountsController::class, 'index'])->name('index');
    Route::get('statement',  [VendorAccountsController::class, 'statement'])->name('statement');
    Route::get('earnings',   [VendorAccountsController::class, 'earnings'])->name('earnings');
});
