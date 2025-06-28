<?php

use App\Http\Controllers\Students\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->group(function () {
    Route::get('create', [PortalController::class, 'create'])->name('portal.create');
    Route::post('store', [PortalController::class, 'store'])->name('portal.store');
    Route::get('{user}/confirmation', [PortalController::class, 'registrationConfirmation'])->name('portal.confirmation');
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('application', [PortalController::class, 'storeApplication'])->name('portal.store-application');
    });
    Route::middleware(['auth', 'verified', 'redirect.student'])->group(function () {
        Route::get('application', [PortalController::class, 'createApplication'])->name('portal.application');
        Route::get('application-confirmation', [PortalController::class, 'applicationConfirmation'])->name('portal.application-confirmation');
        Route::get('dashboard', [PortalController::class, 'dashboard'])->name('portal.dashboard');
        Route::get('personal-details', [PortalController::class, 'personal'])->name('portal.personal-details');
        Route::get('programs', [PortalController::class, 'programs'])->name('portal.programs');
        Route::get('sponsors', [PortalController::class, 'sponsors'])->name('portal.sponsors');
        Route::get('contacts', [PortalController::class, 'contacts'])->name('portal.contacts');
        Route::get('financial-record', [PortalController::class, 'financialRecord'])->name('portal.financial-record');
        Route::get('academic-record', [PortalController::class, 'academicRecord'])->name('portal.academic-record');
        # =============================== META ====================================
        Route::post('contacts', [PortalController::class, 'storeContactDetails'])->name('portal.contacts.store');
        Route::post('addresses', [PortalController::class, 'storeAddressDetails'])->name('portal.address.store');
    });
});
