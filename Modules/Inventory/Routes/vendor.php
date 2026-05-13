<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => 'vendor/inventory',
    'as'         => 'vendor.inventory.',
    'middleware' => ['vendor', 'module:inventory'],
], function () {

    // Inventory list
    Route::get('/', 'Vendor\VendorInventoryController@index')->name('index');
    Route::get('/item/{itemId}', 'Vendor\VendorInventoryController@itemDetail')->name('item-detail');
    Route::post('/opening-stock', 'Vendor\VendorInventoryController@openingStock')->name('opening-stock');
    Route::post('/item/{itemId}/valuation', 'Vendor\VendorInventoryController@saveItemValuation')->name('item-valuation');
    Route::post('/valuation-method', 'Vendor\VendorInventoryController@saveStoreValuation')->name('store-valuation');

    // Special transactions
    Route::post('/damaged', 'Vendor\VendorInventoryController@damaged')->name('damaged');
    Route::post('/broken', 'Vendor\VendorInventoryController@broken')->name('broken');
    Route::post('/internal-use', 'Vendor\VendorInventoryController@internalUse')->name('internal-use');

    // Suppliers
    Route::resource('suppliers', 'Vendor\VendorSupplierController')->except(['show']);

    // Purchases
    Route::resource('purchases', 'Vendor\VendorPurchaseController');
    Route::post('purchases/{id}/receive', 'Vendor\VendorPurchaseController@receive')->name('purchases.receive');
    Route::post('purchases/{id}/return', 'Vendor\VendorPurchaseController@purchaseReturn')->name('purchases.return');

    // Adjustments
    Route::resource('adjustments', 'Vendor\VendorAdjustmentController');

    // Reorder points
    Route::get('/reorder-points', 'Vendor\VendorInventoryController@reorderPoints')->name('reorder-points.index');
    Route::post('/reorder-points', 'Vendor\VendorInventoryController@setReorderPoint')->name('reorder-points.set');
    Route::delete('/reorder-points/{id}', 'Vendor\VendorInventoryController@deleteReorderPoint')->name('reorder-points.destroy');

    // Reports
    Route::prefix('reports')->as('reports.')->group(function () {
        Route::get('stock-ledger', 'Vendor\VendorReportController@stockLedger')->name('stock-ledger');
        Route::get('valuation', 'Vendor\VendorReportController@valuation')->name('valuation');
        Route::get('low-stock', 'Vendor\VendorReportController@lowStock')->name('low-stock');
        Route::get('expiring', 'Vendor\VendorReportController@expiring')->name('expiring');
        Route::get('purchases', 'Vendor\VendorReportController@purchases')->name('purchases');
        Route::get('damage-loss', 'Vendor\VendorReportController@damageLoss')->name('damage-loss');
        Route::get('adjustment-history', 'Vendor\VendorReportController@adjustmentHistory')->name('adjustment-history');
        Route::get('movements', 'Vendor\VendorReportController@movements')->name('movements');
        Route::get('export/{type}', 'Vendor\VendorReportController@export')->name('export');
    });
});
