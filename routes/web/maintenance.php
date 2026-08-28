<?php

use App\Http\Controllers\Maintenance\ApplicationExportController;
use App\Http\Controllers\Maintenance\FaultyApplicationsController;
use App\Http\Controllers\Maintenance\MaintenanceController;
use App\Http\Controllers\Maintenance\StudentEnrollmentExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('maintenance')->middleware(['auth', 'can:accessDataMaintenance'])->group(function (): void {
    Route::get('/', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('/account-purge-archives', [MaintenanceController::class, 'accountPurgeArchives'])
        ->name('maintenance.account-purge-archives');
    Route::post('/account-purge-archives/{archive}/restore', [MaintenanceController::class, 'restoreAccountPurgeArchive'])
        ->name('maintenance.account-purge-archives.restore');
    Route::delete('/account-purge-archives/{archive}/flush', [MaintenanceController::class, 'flushAccountPurgeArchive'])
        ->name('maintenance.account-purge-archives.flush');
    Route::get('/non-enrolled-student-users', [MaintenanceController::class, 'nonEnrolledStudentUsers'])
        ->name('maintenance.non-enrolled-student-users');
    Route::post('/non-enrolled-student-users/bulk-purge', [MaintenanceController::class, 'bulkPurgeNonEnrolledStudentUsers'])
        ->name('maintenance.non-enrolled-student-users.bulk-purge');
    Route::delete('/non-enrolled-student-users/{user}', [MaintenanceController::class, 'purgeNonEnrolledStudentUser'])
        ->name('maintenance.non-enrolled-student-users.purge');
    Route::get('/exports/counts', [MaintenanceController::class, 'exportCounts'])
        ->name('maintenance.exports.counts');
    Route::get('/exports/student-enrollment', [StudentEnrollmentExportController::class, 'index'])
        ->name('maintenance.exports.student-enrollment.preview');
    Route::post('/exports/student-enrollment', [StudentEnrollmentExportController::class, 'store'])
        ->name('maintenance.exports.student-enrollment');
    Route::get('/exports/application', [ApplicationExportController::class, 'index'])
        ->name('maintenance.exports.application.preview');
    Route::post('/exports/application', [ApplicationExportController::class, 'store'])
        ->name('maintenance.exports.application');
    Route::get('/faulty-applications', [FaultyApplicationsController::class, 'index'])
        ->name('maintenance.faulty-applications');
    Route::get('/faulty-applications/data', [FaultyApplicationsController::class, 'data'])
        ->name('maintenance.faulty-applications.data');
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
    Route::post('/faulty-student-ids/bulk-fix', [MaintenanceController::class, 'bulkFixFaultyStudentIdNumbers'])
        ->name('maintenance.faulty-student-ids.bulk-fix');
    Route::patch('/faulty-student-ids/merge/applications/{student_application}/reject', [MaintenanceController::class, 'rejectMergePreviewApplication'])
        ->name('maintenance.faulty-student-ids.merge.reject-application');
    Route::post('/faulty-student-ids/merge', [MaintenanceController::class, 'mergeFaultyStudentAccounts'])
        ->name('maintenance.faulty-student-ids.merge.execute');
    Route::get('/faulty-student-ids/{student}/merge-preview', [MaintenanceController::class, 'mergeFaultyStudentPreviewData'])
        ->name('maintenance.faulty-student-ids.merge-preview');
    Route::get('/faulty-student-ids/{student}/merge', [MaintenanceController::class, 'mergeFaultyStudentPreview'])
        ->name('maintenance.faulty-student-ids.merge');
    Route::patch('/faulty-student-ids/{student}', [MaintenanceController::class, 'fixFaultyStudentIdNumber'])
        ->name('maintenance.faulty-student-ids.fix');
    Route::get('/staff/export', [MaintenanceController::class, 'exportStaff'])
        ->name('maintenance.staff.export');
    Route::get('/staff-import/template', [MaintenanceController::class, 'downloadStaffImportTemplate'])
        ->name('maintenance.staff-import.template');
    Route::post('/staff-import/preview', [MaintenanceController::class, 'previewStaffImport'])
        ->name('maintenance.staff-import.preview');
    Route::post('/staff-import/lookups', [MaintenanceController::class, 'createStaffImportLookup'])
        ->name('maintenance.staff-import.lookups.create');
    Route::post('/staff-import', [MaintenanceController::class, 'processStaffImport'])
        ->name('maintenance.staff-import.process');
    Route::get('/apprentice-management', [MaintenanceController::class, 'apprenticeManagement'])
        ->name('maintenance.apprentice-management');
    Route::get('/apprentice-management/template', [MaintenanceController::class, 'downloadApprenticeImportTemplate'])
        ->name('maintenance.apprentice-management.template');
    Route::post('/apprentice-management/preview', [MaintenanceController::class, 'previewApprenticeImport'])
        ->name('maintenance.apprentice-management.preview');
    Route::post('/apprentice-management/process', [MaintenanceController::class, 'processApprenticeImport'])
        ->name('maintenance.apprentice-management.process');
    Route::post('/apprentice-management/refresh-row', [MaintenanceController::class, 'refreshApprenticeImportRow'])
        ->name('maintenance.apprentice-management.refresh-row');
    Route::get('/sponsored-students', [MaintenanceController::class, 'sponsoredStudents'])
        ->name('maintenance.sponsored-students');
    Route::get('/sponsored-students/template', [MaintenanceController::class, 'downloadSponsoredStudentImportTemplate'])
        ->name('maintenance.sponsored-students.template');
    Route::post('/sponsored-students/preview', [MaintenanceController::class, 'previewSponsoredStudentImport'])
        ->name('maintenance.sponsored-students.preview');
    Route::post('/sponsored-students/process', [MaintenanceController::class, 'processSponsoredStudentImport'])
        ->name('maintenance.sponsored-students.process');
});
