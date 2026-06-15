<?php

use App\Http\Controllers\Students\PortalController;
use App\Http\Controllers\Students\StudentOLevelResultsController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->group(function () {
    Route::get('create-account', [PortalController::class, 'create'])->name('portal.create');
    Route::post('store', [PortalController::class, 'store'])->name('portal.store');
    Route::get('{user}/confirmation', [PortalController::class, 'registrationConfirmation'])->name('portal.confirmation');
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('application', [PortalController::class, 'storeApplication'])->name('portal.store-application');
        Route::get('application/level', [PortalController::class, 'levelOptions'])->name('portal.application.level-options');
        Route::post('application/select-level', [PortalController::class, 'selectLevel'])->name('portal.application.select-level');
    });
    Route::middleware(['auth', 'verified', 'redirect.student'])->group(function () {
        Route::get('application/fee-payment', [PortalController::class, 'registrationFeePaymentOptions'])->name('portal.application.fee-payment');
        Route::get('application/create', [PortalController::class, 'createApplication'])->name('portal.application.create');
        Route::get('application/confirm', [PortalController::class, 'confirmApplication'])->name('portal.application.confirm');
        Route::get('application/{student_program}/view', [PortalController::class, 'viewApplication'])->name('portal.application.view');
        Route::get('application/{student_program}/edit', [PortalController::class, 'editApplication'])->name('portal.application.edit');
        Route::put('application/{student_program}/update', [PortalController::class, 'updateApplication'])->name('portal.application.update');
        Route::get('application/{student}/add-program', [PortalController::class, 'createProgram'])->name('portal.add-program');
        Route::post('application/{student}/add-program', [PortalController::class, 'storeProgram'])->name('portal.program.store');
        Route::get('applications', [PortalController::class, 'applications'])->name('portal.applications');
        Route::get('dashboard', [PortalController::class, 'dashboard'])->name('portal.dashboard');
        Route::prefix('profile')->name('portal.profile.')->group(function () {
            Route::get('personal-information', [PortalController::class, 'profilePersonalInformation'])->name('personal-information');
            Route::get('programs', [PortalController::class, 'profilePrograms'])->name('programs');
            Route::get('applications', [PortalController::class, 'profileApplications'])->name('applications');
            Route::get('financials', [PortalController::class, 'profileFinancials'])->name('financials');
            Route::get('accommodations', [PortalController::class, 'profileAccommodations'])->name('accommodations');
            Route::get('accommodations/pay', [PortalController::class, 'accommodationFeePaymentOptions'])->name('accommodations.pay');
            Route::get('documents', [PortalController::class, 'profileDocuments'])->name('documents');
            Route::get('authentication', [PortalController::class, 'profileAuthentication'])->name('authentication');
        });
        Route::get('personal-details', [PortalController::class, 'personal'])->name('portal.personal-details');
        Route::get('programs', [PortalController::class, 'programs'])->name('portal.programs');
        Route::get('financial-record', [PortalController::class, 'financialRecord'])->name('portal.financial-record');
        Route::get('academic-record', [PortalController::class, 'academicRecord'])->name('portal.academic-record');
        Route::get('list-o-levels', [StudentOLevelResultsController::class, 'index'])->name('portal.list-o-levels');
        Route::get('manage-o-level-results', [StudentOLevelResultsController::class, 'manage'])->name('portal.manage-o-level-results');
        Route::post('store-o-level-results/{student}', [StudentOLevelResultsController::class, 'store'])->name('portal.store-o-level-results');
        Route::get('get-o-level-results/{student}', [StudentOLevelResultsController::class, 'loadStudentOLevelResults'])->name('portal.get-o-level-results');
        Route::put('update-o-level-results/{student_academic_result}', [StudentOLevelResultsController::class, 'update'])->name('portal.update-o-level-results');
        Route::delete('delete-o-level-results/{student_academic_result}', [StudentOLevelResultsController::class, 'destroy'])->name('portal.delete-o-level-results');
        // =============================================== META =========================================================
        Route::post('contacts', [PortalController::class, 'storeContactDetails'])->name('portal.contacts.store');
        Route::post('addresses', [PortalController::class, 'storeAddressDetails'])->name('portal.address.store');
        Route::post('next-of-kins', [PortalController::class, 'storeNextOfKinDetails'])->name('portal.next-of-kins.store');
        // =============================================== MISC =========================================================
        Route::get('applications/errors/{message}', [PortalController::class, 'errors'])->name('portal.applications.errors');
    });
});
