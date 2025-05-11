<?php

use App\Http\Controllers\Api\V1\AddressTypes\AddressTypeController;
use App\Http\Controllers\Api\V1\Institution\CourseController;
use App\Http\Controllers\Api\V1\Institution\DepartmentController;
use App\Http\Controllers\Api\V1\Institution\LevelController;
use App\Http\Controllers\Api\V1\Provinces\ProvinceController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    # ==================================== ADDRESS TYPES =================================================
    Route::apiResource('address-types', AddressTypeController::class)->names('v1.address-types');
    # ==================================== PROVINCES ======================================================
    Route::apiResource('provinces', ProvinceController::class)->names('v1.provinces');
    # ==================================== DEPARTMENTS ======================================================
    Route::apiResource('departments', DepartmentController::class)->names('v1.departments');
    # ==================================== LEVELS ======================================================
    Route::apiResource('levels', LevelController::class)->names('v1.levels');
    # ==================================== COURSES ======================================================
    Route::apiResource('courses', CourseController::class)->names('v1.courses');
});
