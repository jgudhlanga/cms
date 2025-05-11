<?php

use App\Http\Controllers\Api\V1\Institution\GetDepartmentMetaDataController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('departments/{institution_department}/metadata', GetDepartmentMetaDataController::class)
        ->name('v1.department-metadata');
});
