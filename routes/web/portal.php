<?php

use App\Http\Controllers\Portal\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->group(function () {
    Route::get('create', [PortalController::class, 'create'])->name('portal.create');
    Route::post('store', [PortalController::class, 'store'])->name('portal.store');
    Route::get('{user}/confirmation', [PortalController::class, 'confirmation'])->name('portal.confirmation');
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('{user}/index', [PortalController::class, 'index'])->name('portal.index');
        Route::get('application/{user}', [PortalController::class, 'createApplication'])->name('portal.application');
        Route::get('{user}/personal-details', [PortalController::class, 'personal'])->name('portal.personal-details');
        Route::get('{user}/contacts', [PortalController::class, 'contacts'])->name('portal.contacts');
        Route::get('{user}/sponsors', [PortalController::class, 'sponsors'])->name('portal.sponsors');
        Route::get('{user}/financial-record', [PortalController::class, 'financialRecord'])->name('portal.financial-record');
        Route::get('{user}/academic-record', [PortalController::class, 'academicRecord'])->name('portal.academic-record');
        Route::get('{user}/programs', [PortalController::class, 'programs'])->name('portal.programs');
        Route::post('{user}/application', [PortalController::class, 'storeApplication'])->name('portal.store-application');
    });
});
