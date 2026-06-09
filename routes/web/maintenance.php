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
    Route::post('/exports/student-enrollment', [MaintenanceController::class, 'exportStudentEnrollment'])
        ->name('maintenance.exports.student-enrollment');
    Route::post('/exports/application', [MaintenanceController::class, 'exportApplication'])
        ->name('maintenance.exports.application');
    Route::get('/staff-import/template', [MaintenanceController::class, 'downloadStaffImportTemplate'])
        ->name('maintenance.staff-import.template');
    Route::post('/staff-import/preview', [MaintenanceController::class, 'previewStaffImport'])
        ->name('maintenance.staff-import.preview');
    Route::post('/staff-import', [MaintenanceController::class, 'processStaffImport'])
        ->name('maintenance.staff-import.process');
});
