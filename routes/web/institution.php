<?php

use App\Http\Controllers\Institution\Setup\IntakePeriodController;
use App\Http\Controllers\Institution\Setup\PortalSetupController;
use App\Http\Controllers\Institution\Departments\DepartmentCourseController;
use App\Http\Controllers\Institution\Departments\DepartmentLevelController;
use App\Http\Controllers\Institution\Departments\InstitutionDepartmentController;
use App\Http\Controllers\Institution\InstitutionController;
use App\Http\Controllers\Institution\Staff\StaffController;
use Illuminate\Support\Facades\Route;


Route::prefix('institution')->middleware('auth')->group(function () {
    Route::get('/', InstitutionController::class)->name('institution.index');
    # ==================================== DEPARTMENTS ================================================================
    Route::post('departments/sync-institution-department', [InstitutionDepartmentController::class, 'syncInstitutionDepartment'])->name('institution-departments.sync');
    Route::put('departments/{department}/restore', [InstitutionDepartmentController::class, 'restore'])->name('institution-departments.restore');
    Route::delete('departments/{department}/force-delete', [InstitutionDepartmentController::class, 'forceDelete'])->name('institution-departments.force-delete');
    Route::resource('departments', InstitutionDepartmentController::class)->names('institution-departments');
    # ==================================== DEPARTMENT LEVELS ================================================================
    Route::post('departments/{institution_department}/sync-levels', [DepartmentLevelController::class, 'syncDepartmentLevels'])->name('department-levels.sync');
    Route::get('departments/{department_level}/requirements', [DepartmentLevelController::class, 'departmentLevelRequirements'])->name('department-levels.requirements');
    Route::post('departments/{department_level}/requirements', [DepartmentLevelController::class, 'updateDepartmentLevelRequirements'])->name('department-levels.store-requirements');
    # ==================================== DEPARTMENT COURSES ================================================================
    Route::post('departments/{institution_department}/sync-courses', [DepartmentCourseController::class, 'syncDepartmentCourses'])->name('department-courses.sync');
    Route::get('departments/{department_course}/show', [DepartmentCourseController::class, 'show'])->name('department-courses.show');
    Route::post('departments/{department_course}/update', [DepartmentCourseController::class, 'update'])->name('department-courses.update');
    # ==================================== INTAKE PERIODS ================================================================
    Route::put('intake-periods/{intake_period}/restore', [IntakePeriodController::class, 'restore'])->name('intake-periods.restore');
    Route::delete('intake-periods/{intake_period}/force-delete', [IntakePeriodController::class, 'forceDelete'])->name('intake-periods.force-delete');
    Route::resource('intake-periods', IntakePeriodController::class)->names('intake-periods');
    # ==================================== DEPARTMENT STAFF ================================================================
    Route::put('staff/{staff}/restore', [StaffController::class, 'restore'])->name('staff.restore');
    Route::delete('staff/{staff}/force-delete', [StaffController::class, 'forceDelete'])->name('staff.force-delete');
    Route::resource('departments.staff', StaffController::class)->names('staff');
    # ================================== PORTAL SETUP ======================================
    Route::prefix('portal/setup')->group(function () {
        Route::get('/', [PortalSetupController::class, 'index'])->name('portal.setup');
        Route::get('/intake-periods', [PortalSetupController::class, 'intakePeriods'])->name('portal.setup.intake-periods');
    });
});

