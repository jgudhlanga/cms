<?php

use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Finance\FinanceExchangeController;
use App\Http\Controllers\Finance\PastelExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('finance')->middleware('auth')->group(function () {
    Route::get('/', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('reconciliation', [FinanceController::class, 'reconciliation'])->name('finance.reconciliation');
    Route::get('pastel-export', [PastelExportController::class, 'index'])->name('finance.pastel-export.index');
    Route::post('pastel-export/download', [PastelExportController::class, 'download'])->name('finance.pastel-export.download');
    Route::delete('pastel-export/linked-students', [PastelExportController::class, 'bulkDestroy'])
        ->name('finance.pastel-export.linked-students.bulk-destroy');
    Route::delete('pastel-export/linked-students/{pastelLinkedStudent}', [PastelExportController::class, 'destroy'])
        ->name('finance.pastel-export.linked-students.destroy');

    Route::put('exchange-rates/{exchange_rate}/restore', [FinanceExchangeController::class, 'restore'])
        ->name('finance.exchange-rates.restore');
    Route::delete('exchange-rates/{exchange_rate}/force-delete', [FinanceExchangeController::class, 'forceDelete'])
        ->name('finance.exchange-rates.force-delete');
    Route::resource('exchange-rates', FinanceExchangeController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('finance.exchange-rates');
});
