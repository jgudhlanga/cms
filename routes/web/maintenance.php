<?php

use App\Http\Controllers\Maintenance\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('maintenance')->middleware(['auth', 'can:root:manage'])->group(function (): void {
    Route::get('/', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('/exports/student-enrollment', [MaintenanceController::class, 'exportStudentEnrollment'])
        ->name('maintenance.exports.student-enrollment');
    Route::post('/exports/application', [MaintenanceController::class, 'exportApplication'])
        ->name('maintenance.exports.application');
});
