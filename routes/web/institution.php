<?php

use App\Http\Controllers\Institution\DepartmentCourseController;
use App\Http\Controllers\Institution\DepartmentLevelController;
use App\Http\Controllers\Institution\InstitutionController;
use App\Http\Controllers\Institution\InstitutionDepartmentController;
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
    # ==================================== DEPARTMENT COURSES ================================================================
    Route::post('departments/{institution_department}/sync-courses', [DepartmentCourseController::class, 'syncDepartmentCourses'])->name('department-courses.sync');
});

