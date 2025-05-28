<?php

use App\Http\Controllers\Api\V1\Institution\GetDepartmentMetaDataController;
use App\Http\Controllers\Api\V1\Institution\InstitutionDepartmentController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('departments/{institution_department}/metadata', GetDepartmentMetaDataController::class)
        ->name('v1.department-metadata');
    Route::get('institution-departments', [InstitutionDepartmentController::class, 'index'])->name('v1.institution-departments.index');
});
