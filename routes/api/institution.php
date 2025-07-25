<?php

use App\Http\Controllers\Api\V1\Institution\DepartmentLevelController;
use App\Http\Controllers\Api\V1\Institution\DepartmentLevelCourseController;
use App\Http\Controllers\Api\V1\Institution\DepartmentMetaDataController;
use App\Http\Controllers\Api\V1\Institution\InstitutionDepartmentController;
use App\Http\Controllers\Api\V1\Institution\IntakePeriodController;
use App\Http\Controllers\Api\V1\Staff\StaffController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('departments/{institution_department}/courses', [DepartmentMetaDataController::class, 'courses'])->name('v1.department-metadata.courses');
    Route::get('departments/{institution_department}/levels', [DepartmentMetaDataController::class, 'levels'])->name('v1.department-metadata.levels');
    Route::get('departments/{institution_department}/staff', [DepartmentMetaDataController::class, 'staff'])->name('v1.department-metadata.staff');
    Route::get('departments/{institution_department}/workflow-steps', [DepartmentMetaDataController::class, 'workflowSteps'])->name('v1.department-metadata.workflow-steps');
    Route::get('departments/{institution_department}/class-sizes', [DepartmentMetaDataController::class, 'classSizes'])->name('v1.department-metadata.class-sizes');
});
Route::prefix('v1')->group(function () {
    Route::get('institution-departments', [InstitutionDepartmentController::class, 'index'])->name('v1.institution-departments.index');
    Route::get('institution-departments/{institution_department}/levels', [DepartmentLevelController::class, 'index'])->name('v1.department-levels.index');
    Route::get('institution-departments/levels/{department_level}/courses', [DepartmentLevelCourseController::class, 'index'])->name('v1.department-level-courses.index');
    Route::get('institution-departments/levels/{department_level}/requirements', [DepartmentLevelController::class, 'levelRequirements'])->name('v1.department-level-requirements');
    Route::apiResource('staff', StaffController::class)->names('v1.staff');
    Route::apiResource('intake-periods', IntakePeriodController::class)->names('v1.intake-periods');
});
