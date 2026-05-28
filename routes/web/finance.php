<?php

use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Finance\FinanceExchangeController;
use Illuminate\Support\Facades\Route;

Route::prefix('finance')->middleware('auth')->group(function () {
    Route::get('/', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('reconciliation', [FinanceController::class, 'reconciliation'])->name('finance.reconciliation');

    Route::put('exchange-rates/{exchange_rate}/restore', [FinanceExchangeController::class, 'restore'])
        ->name('finance.exchange-rates.restore');
    Route::delete('exchange-rates/{exchange_rate}/force-delete', [FinanceExchangeController::class, 'forceDelete'])
        ->name('finance.exchange-rates.force-delete');
    Route::resource('exchange-rates', FinanceExchangeController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('finance.exchange-rates');
});
