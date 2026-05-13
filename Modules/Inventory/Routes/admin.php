<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => 'admin/inventory',
    'as'         => 'admin.inventory.',
    'middleware' => ['admin', 'current-module', 'module:inventory'],
], function () {

    // Central inventory
    Route::get('/', 'Admin\CentralInventoryController@index')->name('central');
    Route::get('/module/{moduleId}', 'Admin\CentralInventoryController@byModule')->name('by-module');
    Route::get('/vendor/{storeId}', 'Admin\CentralInventoryController@byVendor')->name('by-vendor');
    Route::get('/module/{moduleId}/vendor/{storeId}', 'Admin\CentralInventoryController@byModuleVendor')->name('by-module-vendor');
    Route::get('/item/{itemId}', 'Admin\CentralInventoryController@itemDetail')->name('item-detail');

    // Opening stock
    Route::post('/opening-stock', 'Admin\CentralInventoryController@openingStock')->name('opening-stock');

    // Valuation method overrides
    Route::post('/item/{itemId}/valuation', 'Admin\CentralInventoryController@saveItemValuation')->name('item-valuation');
    Route::post('/store/{storeId}/valuation', 'Admin\CentralInventoryController@saveStoreValuation')->name('store-valuation');

    // Reorder Points
    Route::resource('reorder-points', 'Admin\ReorderPointController');

    // Suppliers
    Route::resource('suppliers', 'Admin\SupplierController');

    // Purchase Orders
    Route::resource('purchases', 'Admin\PurchaseOrderController');
    Route::post('purchases/{id}/receive', 'Admin\PurchaseOrderController@receive')->name('purchases.receive');
    Route::post('purchases/{id}/return', 'Admin\PurchaseOrderController@purchaseReturn')->name('purchases.return');

    // Stock Transfers
    Route::resource('transfers', 'Admin\StockTransferController');
    Route::post('transfers/{id}/receive', 'Admin\StockTransferController@receive')->name('transfers.receive');

    // Adjustments
    Route::resource('adjustments', 'Admin\InventoryAdjustmentController');
    Route::post('adjustments/{id}/approve', 'Admin\InventoryAdjustmentController@approve')->name('adjustments.approve');
    Route::post('adjustments/{id}/reject', 'Admin\InventoryAdjustmentController@reject')->name('adjustments.reject');

    // Special transactions
    Route::post('/damaged', 'Admin\CentralInventoryController@damaged')->name('damaged');
    Route::post('/broken', 'Admin\CentralInventoryController@broken')->name('broken');
    Route::post('/internal-use', 'Admin\CentralInventoryController@internalUse')->name('internal-use');

    // Reports
    Route::prefix('reports')->as('reports.')->group(function () {
        Route::get('stock-ledger', 'Admin\InventoryReportController@stockLedger')->name('stock-ledger');
        Route::get('valuation', 'Admin\InventoryReportController@valuation')->name('valuation');
        Route::get('low-stock', 'Admin\InventoryReportController@lowStock')->name('low-stock');
        Route::get('expiring', 'Admin\InventoryReportController@expiring')->name('expiring');
        Route::get('dead-stock', 'Admin\InventoryReportController@deadStock')->name('dead-stock');
        Route::get('purchases', 'Admin\InventoryReportController@purchases')->name('purchases');
        Route::get('damage-loss', 'Admin\InventoryReportController@damageLoss')->name('damage-loss');
        Route::get('movements', 'Admin\InventoryReportController@movements')->name('movements');
        Route::get('valuation-summary', 'Admin\InventoryReportController@valuationSummary')->name('valuation-summary');
        Route::get('module-summary', 'Admin\InventoryReportController@moduleStock')->name('module-summary');
        Route::get('vendor-summary', 'Admin\InventoryReportController@vendorStock')->name('vendor-summary');
        Route::get('adjustment-history', 'Admin\InventoryReportController@adjustmentHistory')->name('adjustment-history');
        Route::get('transfer-history', 'Admin\InventoryReportController@transferHistory')->name('transfer-history');
        Route::get('cogs', 'Admin\InventoryReportController@cogs')->name('cogs');
        Route::get('export/{type}', 'Admin\InventoryReportController@export')->name('export');
    });
});
