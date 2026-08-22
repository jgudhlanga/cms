<?php

use App\Http\Controllers\Enrolments\ClassListController;
use App\Http\Controllers\Enrolments\EnrolmentApplicantLookupController;
use App\Http\Controllers\Students\StudentApplicationController;
use Illuminate\Support\Facades\Route;

// ===================================== ENROLMENTS ====================================================================
Route::prefix('enrolments')->middleware('auth')->group(function () {
    Route::get('department-applications/{institution_department}', [StudentApplicationController::class, 'departmentEnrolments'])
        ->middleware('class-list.type')
        ->name('enrolments.department-applications');
    Route::post('store-class-list', [ClassListController::class, 'store'])->name('enrolments.store-class-list');
    Route::post('bulk-add-to-class-list', [ClassListController::class, 'bulkAdd'])->name('enrolments.bulk-add-to-class-list');
    Route::post('transition-class-list', [ClassListController::class, 'transition'])->name('enrolments.transition-class-list');
    Route::post('purge-class-list', [ClassListController::class, 'purge'])->name('enrolments.purge-class-list');
    Route::put('update-class-list/{student_application}', [ClassListController::class, 'update'])->name('enrolments.update-class-list');
    Route::put('reject-application/{student_application}', [ClassListController::class, 'rejectApplication'])->name('enrolments.reject-application');
    Route::post('add-to-class-list/{student_application}', [ClassListController::class, 'addToClassList'])->name('enrolments.add-to-class-list');
    Route::get('applicant-lookup', EnrolmentApplicantLookupController::class)
        ->middleware('class-list.type')
        ->name('enrolments.applicant-lookup');
    Route::get('{institution_department}/class-lists/{department_level}', [ClassListController::class, 'classLists'])
        ->middleware('class-list.type')
        ->name('enrolments.class-lists');
    Route::get('/verify/{student_application}', [ClassListController::class, 'verify'])
        ->middleware('can:verify:class-lists')
        ->name('enrolments.verify');
    Route::get('/confirm/{student_application}', [ClassListController::class, 'confirm'])
        ->middleware('can:confirm:class-lists')
        ->name('enrolments.confirm');
});
Route::middleware('auth')->resource('enrolments', StudentApplicationController::class)->names('enrolments');
