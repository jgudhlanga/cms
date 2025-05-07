<?php

use App\Http\Controllers\Api\V1\Institution\DepartmentMetaDataController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('departments/{department}/metadata', DepartmentMetadataController::class)
        ->name('v1.department-metadata');
});
