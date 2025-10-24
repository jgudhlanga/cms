<?php

use App\Http\Controllers\Enrolments\ClassListController;
use App\Http\Controllers\Students\StudentController;
use App\Http\Controllers\Students\StudentProgramController;
use Illuminate\Support\Facades\Route;

// ===================================== ENROLMENTS ====================================================================
Route::prefix('enrolments')->middleware('auth')->group(function () {
    Route::get('faulty-applications', [StudentProgramController::class, 'faultyApplications'])->name('enrolments.faulty-applications');
    Route::post('search-profile', [StudentController::class, 'searchProfile'])->name('enrolments.search-profile');
    Route::get('lookup', [StudentController::class, 'enrolmentLookup'])->name('enrolments.enrolment-lookup');
    Route::get('create/{payment_mode}', [StudentController::class, 'createProfile'])->name('enrolments.create-profile');
    Route::get('show-enrolment/{student}', [StudentController::class, 'showProfile'])->name('enrolments.show-profile');
    Route::post('store-class-list', [ClassListController::class, 'store'])->name('enrolments.store-class-list');
    Route::put('update-class-list/{classList}', [ClassListController::class, 'update'])->name('enrolments.update-class-list');
});
Route::middleware('auth')->resource('enrolments', StudentProgramController::class)->names('enrolments');
