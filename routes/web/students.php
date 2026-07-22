<?php

use App\Http\Controllers\Students\AcademicRecordController;
use App\Http\Controllers\Students\SponsorController;
use App\Http\Controllers\Students\StudentController;
use App\Http\Controllers\Students\UserStudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('students')->middleware('auth')->group(function () {
    // ==================================== SPONSORS ================================================================
    Route::post('sponsors', [SponsorController::class, 'store'])->name('sponsors.store');
    Route::put('sponsors/{sponsor}/restore', [SponsorController::class, 'restore'])->name('sponsors.restore');
    Route::put('sponsors/{sponsor}/update', [SponsorController::class, 'update'])->name('sponsors.update');
    Route::delete('sponsors/{sponsor}/delete', [SponsorController::class, 'destroy'])->name('sponsors.destroy');
    Route::delete('sponsors/{sponsor}/force-delete', [SponsorController::class, 'forceDelete'])->name('sponsors.force-delete');
    // ==================================== ACADEMIC RECORDS ================================================================
    Route::post('academic-records', [AcademicRecordController::class, 'store'])->name('academic-records.store');
    Route::put('academic-records/{academic_record}/restore', [AcademicRecordController::class, 'restore'])->name('academic-records.restore');
    Route::put('academic-records/{academic_record}/update', [AcademicRecordController::class, 'update'])->name('academic-records.update');
    Route::delete('academic-records/{academic_record}/delete', [AcademicRecordController::class, 'destroy'])->name('academic-records.destroy');
    Route::delete('academic-records/{academic_record}/force-delete', [AcademicRecordController::class, 'forceDelete'])->name('academic-records.force-delete');
});

// ===================================== STUDENTS ======================================================================
Route::prefix('students')->middleware('auth')->group(function () {
    Route::get('export', [StudentController::class, 'export'])->name('students.export');
    Route::patch('{student}/id-number', [StudentController::class, 'updateIdNumber'])
        ->name('students.id-number.update');
    Route::delete('{student}/purge', [StudentController::class, 'purge'])
        ->middleware('can:root:manage')
        ->name('students.purge');
});
Route::prefix('students')->middleware('auth')->group(function () {
    // get student and programs through user account
    Route::get('{user}/profile', [UserStudentController::class, 'index'])->name('students.profile');
    Route::get('program/{student_application}/edit', [UserStudentController::class, 'edit'])->name('students.program-edit');
    Route::put('program/{student_application}/update', [UserStudentController::class, 'updateProgram'])->name('students.program-update');
});
Route::middleware('auth')->resource('students', StudentController::class)->names('students');
