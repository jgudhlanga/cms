<?php

use App\Http\Controllers\Integrations\PaymentController;
use App\Http\Controllers\Students\PortalController;
use App\Http\Controllers\Students\ReturningStudentController;
use App\Http\Controllers\Students\StudentOLevelResultsController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->group(function () {
    Route::get('registration/maintenance', [PortalController::class, 'registrationMaintenance'])
        ->name('portal.registration.maintenance');

    Route::middleware('registration.open')->group(function () {
        Route::get('create-account', [PortalController::class, 'create'])->name('portal.create');
        Route::post('store', [PortalController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('portal.store');
    });

    Route::get('{user}/confirmation', [PortalController::class, 'registrationConfirmation'])->name('portal.confirmation');
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('application', [PortalController::class, 'storeApplication'])
            ->middleware(['registration.open', 'application.track', 'redirect.student'])
            ->name('portal.store-application');
        Route::middleware(['registration.open', 'application.track'])->group(function () {
            Route::get('application/track', [PortalController::class, 'chooseTrack'])->name('portal.application.track');
            Route::post('application/track', [PortalController::class, 'selectTrack'])->name('portal.application.select-track');
            Route::get('application/apprentice', [PortalController::class, 'apprenticeApplication'])->name('portal.application.apprentice');
            Route::get('application/level', [PortalController::class, 'levelOptions'])->name('portal.application.level-options');
            Route::post('application/select-level', [PortalController::class, 'selectLevel'])->name('portal.application.select-level');
        });
        Route::post('application/apprentice', [PortalController::class, 'storeApprenticeApplication'])
            ->middleware(['registration.open', 'application.track', 'redirect.student'])
            ->name('portal.application.apprentice.store');
    });
    Route::middleware(['auth', 'verified', 'redirect.student'])->group(function () {
        Route::middleware(['registration.open', 'application.track'])->group(function () {
            Route::get('application/fee-payment', [PaymentController::class, 'registrationFeePaymentOptions'])->name('portal.application.fee-payment');
            Route::get('application/create', [PortalController::class, 'createApplication'])->name('portal.application.create');
            Route::get('application/confirm', [PortalController::class, 'confirmApplication'])->name('portal.application.confirm');
            Route::get('application/{student}/add-program', [PortalController::class, 'createProgram'])->name('portal.add-program');
            Route::post('application/{student}/add-program', [PortalController::class, 'storeProgram'])->name('portal.program.store');
        });

        Route::middleware('registration.open')->group(function () {
            Route::prefix('returning-student')->name('portal.returning-student.')->group(function () {
                Route::get('continue', [ReturningStudentController::class, 'showContinue'])->name('continue.show');
                Route::post('continue', [ReturningStudentController::class, 'continueInClass'])->name('continue');
            });

            Route::get('application/returning', [PortalController::class, 'returningApplication'])->name('portal.application.returning');
            Route::get('application/returning/confirm', [PortalController::class, 'confirmReturningApplication'])->name('portal.application.returning.confirm');
            Route::post('application/returning', [PortalController::class, 'storeReturningApplication'])->name('portal.application.returning.store');
        });
        Route::get('application/{student_application}/view', [PortalController::class, 'viewApplication'])->name('portal.application.view');
        Route::get('application/{student_application}/edit', [PortalController::class, 'editApplication'])->name('portal.application.edit');
        Route::put('application/{student_application}/update', [PortalController::class, 'updateApplication'])->name('portal.application.update');
        Route::get('applications', [PortalController::class, 'applications'])->name('portal.applications');
        Route::get('dashboard', [PortalController::class, 'dashboard'])->name('portal.dashboard');
        Route::prefix('profile')->name('portal.profile.')->group(function () {
            Route::get('personal-information', [PortalController::class, 'profilePersonalInformation'])->name('personal-information');
            Route::put('personal-information', [PortalController::class, 'updatePersonalDetails'])->name('personal-information.update');
            Route::get('programs', [PortalController::class, 'profilePrograms'])->name('programs');
            Route::get('applications', [PortalController::class, 'profileApplications'])->name('applications');
            Route::get('applications/level', [PortalController::class, 'profileApplicationLevelOptions'])->name('applications.level');
            Route::post('applications/acknowledge', [PortalController::class, 'acknowledgeApplicationHub'])->name('applications.acknowledge');
            Route::post('applications/select-level', [PortalController::class, 'selectApplicationLevel'])->name('applications.select-level');
            Route::get('financials', [PortalController::class, 'profileFinancials'])->name('financials');
            Route::get('accommodations', [PortalController::class, 'profileAccommodations'])->name('accommodations');
            Route::get('accommodations/pay/currency', [PaymentController::class, 'accommodationFeeCurrencySelection'])->name('accommodations.pay.currency');
            Route::get('accommodations/pay', [PaymentController::class, 'accommodationFeePaymentOptions'])->name('accommodations.pay');
            Route::get('documents', [PortalController::class, 'profileDocuments'])->name('documents');
            Route::get('authentication', [PortalController::class, 'profileAuthentication'])
                ->middleware('impersonate.protect')
                ->name('authentication');
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
