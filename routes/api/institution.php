<?php

use App\Http\Controllers\Api\V1\Institution\DepartmentLevelController;
use App\Http\Controllers\Api\V1\Institution\DepartmentLevelCourseController;
use App\Http\Controllers\Api\V1\Institution\GetDepartmentMetaDataController;
use App\Http\Controllers\Api\V1\Institution\InstitutionDepartmentController;

Route::prefix('v1')->group(function () {
    Route::get('departments/{institution_department}/metadata', GetDepartmentMetaDataController::class)->name('v1.department-metadata')
        ->middleware('auth:sanctum');
    Route::get('institution-departments', [InstitutionDepartmentController::class, 'index'])->name('v1.institution-departments.index');
    Route::get('institution-departments/{institution_department}/levels', [DepartmentLevelController::class, 'index'])->name('v1.department-levels.index');
    Route::get('institution-departments/levels/{department_level}/courses', [DepartmentLevelCourseController::class, 'index'])->name('v1.department-level-courses.index');
});
