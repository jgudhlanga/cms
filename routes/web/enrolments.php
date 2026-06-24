<?php

use App\Http\Controllers\Enrolments\ClassListController;
use App\Http\Controllers\Students\StudentController;
use App\Http\Controllers\Students\StudentApplicationController;
use Illuminate\Support\Facades\Route;

// ===================================== ENROLMENTS ====================================================================
Route::prefix('enrolments')->middleware('auth')->group(function () {
    Route::get('faulty-applications', [StudentApplicationController::class, 'faultyApplications'])->name('enrolments.faulty-applications');
    Route::get('department-applications/{institution_department}', [StudentApplicationController::class, 'departmentEnrolments'])->name('enrolments.department-applications');
    Route::post('search-profile', [StudentController::class, 'searchProfile'])->name('enrolments.search-profile');
    Route::get('lookup', [StudentController::class, 'enrolmentLookup'])->name('enrolments.enrolment-lookup');
    Route::get('create/{payment_mode}', [StudentController::class, 'createProfile'])->name('enrolments.create-profile');
    Route::get('show-enrolment/{student}', [StudentController::class, 'showProfile'])->name('enrolments.show-profile');
    Route::post('store-class-list', [ClassListController::class, 'store'])->name('enrolments.store-class-list');
    Route::put('update-class-list/{student_application}', [ClassListController::class, 'update'])->name('enrolments.update-class-list');
    Route::put('reject-application/{student_application}', [ClassListController::class, 'rejectApplication'])->name('enrolments.reject-application');
    Route::post('add-to-class-list/{student_application}', [ClassListController::class, 'addToClassList'])->name('enrolments.add-to-class-list');
    Route::get('{institution_department}/class-lists/{department_level}', [ClassListController::class, 'classLists'])->name('enrolments.class-lists');
    Route::get('/verify/{student_application}', [ClassListController::class, 'verify'])->name('enrolments.verify');
    Route::get('/confirm/{student_application}', [ClassListController::class, 'confirm'])->name('enrolments.confirm');
});
Route::middleware('auth')->resource('enrolments', StudentApplicationController::class)->names('enrolments');
