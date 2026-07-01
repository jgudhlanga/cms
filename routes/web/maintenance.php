<?php

use App\Http\Controllers\Maintenance\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('maintenance')->middleware(['auth', 'can:root:manage'])->group(function (): void {
    Route::get('/', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('/non-enrolled-student-users', [MaintenanceController::class, 'nonEnrolledStudentUsers'])
        ->name('maintenance.non-enrolled-student-users');
    Route::post('/non-enrolled-student-users/bulk-purge', [MaintenanceController::class, 'bulkPurgeNonEnrolledStudentUsers'])
        ->name('maintenance.non-enrolled-student-users.bulk-purge');
    Route::delete('/non-enrolled-student-users/{user}', [MaintenanceController::class, 'purgeNonEnrolledStudentUser'])
        ->name('maintenance.non-enrolled-student-users.purge');
    Route::get('/exports/counts', [MaintenanceController::class, 'exportCounts'])
        ->name('maintenance.exports.counts');
    Route::post('/exports/student-enrollment', [MaintenanceController::class, 'exportStudentEnrollment'])
        ->name('maintenance.exports.student-enrollment');
    Route::post('/exports/application', [MaintenanceController::class, 'exportApplication'])
        ->name('maintenance.exports.application');
    Route::get('/verified-students-final-enrolment', [MaintenanceController::class, 'verifiedStudentsFinalEnrolment'])
        ->name('maintenance.verified-students-final-enrolment');
    Route::get('/verified-students-final-enrolment/data', [MaintenanceController::class, 'verifiedStudentsFinalEnrolmentData'])
        ->name('maintenance.verified-students-final-enrolment.data');
    Route::get('/verified-students-final-enrolment/summary', [MaintenanceController::class, 'verifiedStudentsFinalEnrolmentSummary'])
        ->name('maintenance.verified-students-final-enrolment.summary');
    Route::post('/verified-students-final-enrolment/run', [MaintenanceController::class, 'dispatchBulkFinaliseEnrolments'])
        ->name('maintenance.verified-students-final-enrolment.run');
    Route::get('/verified-students-final-enrolment/runs/{runId}', [MaintenanceController::class, 'bulkFinaliseEnrolmentsRunStatus'])
        ->name('maintenance.verified-students-final-enrolment.run-status');
    Route::get('/faulty-student-ids', [MaintenanceController::class, 'faultyStudentIds'])
        ->name('maintenance.faulty-student-ids');
    Route::get('/faulty-student-ids/data', [MaintenanceController::class, 'faultyStudentIdNumbers'])
        ->name('maintenance.faulty-student-ids.data');
    Route::patch('/faulty-student-ids/merge/applications/{student_application}/reject', [MaintenanceController::class, 'rejectMergePreviewApplication'])
        ->name('maintenance.faulty-student-ids.merge.reject-application');
    Route::post('/faulty-student-ids/merge', [MaintenanceController::class, 'mergeFaultyStudentAccounts'])
        ->name('maintenance.faulty-student-ids.merge.execute');
    Route::get('/faulty-student-ids/{student}/merge', [MaintenanceController::class, 'mergeFaultyStudentPreview'])
        ->name('maintenance.faulty-student-ids.merge');
    Route::patch('/faulty-student-ids/{student}', [MaintenanceController::class, 'fixFaultyStudentIdNumber'])
        ->name('maintenance.faulty-student-ids.fix');
    Route::get('/staff-import/template', [MaintenanceController::class, 'downloadStaffImportTemplate'])
        ->name('maintenance.staff-import.template');
    Route::post('/staff-import/preview', [MaintenanceController::class, 'previewStaffImport'])
        ->name('maintenance.staff-import.preview');
    Route::post('/staff-import/lookups', [MaintenanceController::class, 'createStaffImportLookup'])
        ->name('maintenance.staff-import.lookups.create');
    Route::post('/staff-import', [MaintenanceController::class, 'processStaffImport'])
        ->name('maintenance.staff-import.process');
});
