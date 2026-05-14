<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounts\Http\Controllers\Admin\AccountingRulesController;
use Modules\Accounts\Http\Controllers\Admin\ChartOfAccountsController;
use Modules\Accounts\Http\Controllers\Admin\JournalEntryController;
use Modules\Accounts\Http\Controllers\Admin\ReportsController;

Route::group([
    'prefix'     => 'admin/accounts',
    'as'         => 'admin.accounts.',
    'middleware' => ['admin', 'current-module'],
], function () {
    // Phase 10.01: Chart of Accounts
    Route::get  ('coa',                      [ChartOfAccountsController::class, 'index'])->name('coa.index');
    Route::get  ('coa/create',               [ChartOfAccountsController::class, 'create'])->name('coa.create');
    Route::post ('coa',                      [ChartOfAccountsController::class, 'store'])->name('coa.store');
    Route::get  ('coa/{account}/edit',       [ChartOfAccountsController::class, 'edit'])->name('coa.edit');
    Route::put  ('coa/{account}',            [ChartOfAccountsController::class, 'update'])->name('coa.update');
    Route::patch('coa/{account}/toggle',     [ChartOfAccountsController::class, 'toggleActive'])->name('coa.toggle');

    // Phase 10.02-10.03: Journal Entries
    Route::get ('journal',                        [JournalEntryController::class, 'index'])->name('journal.index');
    Route::get ('journal/create',                 [JournalEntryController::class, 'create'])->name('journal.create');
    Route::post('journal',                        [JournalEntryController::class, 'store'])->name('journal.store');
    Route::get ('journal/{journalEntry}',         [JournalEntryController::class, 'show'])->name('journal.show');
    Route::post('journal/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('journal.reverse');

    // Phase 10.04: Accounting Rules
    Route::get ('rules',             [AccountingRulesController::class, 'index'])->name('rules.index');
    Route::get ('rules/create',      [AccountingRulesController::class, 'create'])->name('rules.create');
    Route::post('rules',             [AccountingRulesController::class, 'store'])->name('rules.store');
    Route::get ('rules/{rule}/edit', [AccountingRulesController::class, 'edit'])->name('rules.edit');
    Route::put ('rules/{rule}',      [AccountingRulesController::class, 'update'])->name('rules.update');

    // Phase 7: core reports
    Route::get('reports/trial-balance',  [ReportsController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('reports/general-ledger', [ReportsController::class, 'generalLedger'])->name('reports.general-ledger');

    // Phase 9: party statements
    Route::get('reports/store-statement', [ReportsController::class, 'storeStatement'])->name('reports.store-statement');
    Route::get('reports/dm-statement',    [ReportsController::class, 'dmStatement'])->name('reports.dm-statement');

    // Phase 8: financial statements & reconciliation
    Route::get('reports/profit-loss',            [ReportsController::class, 'profitAndLoss'])->name('reports.profit-loss');
    Route::get('reports/balance-sheet',          [ReportsController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('reports/tax-report',             [ReportsController::class, 'taxReport'])->name('reports.tax-report');
    Route::get('reports/cod-reconciliation',     [ReportsController::class, 'codReconciliation'])->name('reports.cod-reconciliation');
    Route::get('reports/gateway-reconciliation', [ReportsController::class, 'gatewayReconciliation'])->name('reports.gateway-reconciliation');
});
