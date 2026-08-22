<?php

use App\Http\Controllers\Admin\Students\IdCardImportController;
use App\Http\Controllers\Admin\Students\IdCardRequestController;
use App\Http\Controllers\Admin\Students\IdCardSettingController;
use App\Http\Controllers\Students\AcademicRecordController;
use App\Http\Controllers\Students\SponsorController;
use App\Http\Controllers\Students\StudentController;
use App\Http\Controllers\Students\StudentEnrolmentProgressController;
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
    Route::patch('{student}/enrolments/{student_enrolment}/status', [StudentEnrolmentProgressController::class, 'updateStatus'])
        ->name('students.enrolments.status.update');
    Route::post('{student}/id-photo', [StudentController::class, 'uploadIdPhoto'])
        ->name('students.id-photo.store');
    Route::delete('{student}/purge', [StudentController::class, 'purge'])
        ->middleware('can:root:manage')
        ->name('students.purge');
});

Route::prefix('students/id-card-requests')->middleware('auth')->name('admin.students.id-card-requests.')->group(function () {
    Route::get('/', [IdCardRequestController::class, 'index'])->name('index');
    Route::get('bulk-print', [IdCardRequestController::class, 'bulkPrint'])->name('bulk-print');
    Route::get('import', [IdCardImportController::class, 'show'])->name('import');
    Route::get('import/template', [IdCardImportController::class, 'template'])->name('import.template');
    Route::post('import/preview', [IdCardImportController::class, 'preview'])->name('import.preview');
    Route::post('import/process', [IdCardImportController::class, 'process'])->name('import.process');
    Route::post('settings', [IdCardSettingController::class, 'update'])->name('settings.update');
    Route::get('{idCardRequest}', [IdCardRequestController::class, 'show'])->name('show');
    Route::post('{idCardRequest}/approve', [IdCardRequestController::class, 'approve'])->name('approve');
    Route::post('{idCardRequest}/reject', [IdCardRequestController::class, 'reject'])->name('reject');
    Route::get('{idCardRequest}/print', [IdCardRequestController::class, 'print'])->name('print');
    Route::post('{idCardRequest}/issue', [IdCardRequestController::class, 'issue'])->name('issue');
});
Route::prefix('students')->middleware('auth')->group(function () {
    // get student and programs through user account
    Route::get('{user}/profile', [UserStudentController::class, 'index'])->name('students.profile');
    Route::get('program/{student_application}/edit', [UserStudentController::class, 'edit'])->name('students.program-edit');
    Route::put('program/{student_application}/update', [UserStudentController::class, 'updateProgram'])->name('students.program-update');
});
Route::middleware('auth')->resource('students', StudentController::class)
    ->except(['create', 'store'])
    ->names('students');
