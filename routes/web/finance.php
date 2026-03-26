<?php

use App\Http\Controllers\Finance\FinanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('finance')->middleware('auth')->group(function () {
    Route::get('/', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('settings', [FinanceController::class, 'settings'])->name('finance.settings');
});
