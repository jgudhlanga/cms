<?php

use App\Http\Controllers\Portal\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->group(function () {
    Route::get('create', [PortalController::class, 'create'])->name('portal.create');
    Route::post('store', [PortalController::class, 'store'])->name('portal.store');
    Route::get('{user}/confirmation', [PortalController::class, 'confirmation'])->name('portal.confirmation');
    Route::get('{user}/index', [PortalController::class, 'index'])->name('portal.index');
    Route::get('application/{user}', [PortalController::class, 'createApplication'])->name('portal.application');
});
