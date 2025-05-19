<?php

use App\Http\Controllers\Api\V1\AddressTypes\AddressTypeController;
use App\Http\Controllers\Api\V1\Countries\CountryController;
use App\Http\Controllers\Api\V1\Districts\DistrictController;
use App\Http\Controllers\Api\V1\Genders\GenderController;
use App\Http\Controllers\Api\V1\Institution\CourseController;
use App\Http\Controllers\Api\V1\Institution\DepartmentController;
use App\Http\Controllers\Api\V1\Institution\LevelController;
use App\Http\Controllers\Api\V1\Provinces\ProvinceController;
use App\Http\Controllers\Api\V1\Titles\TitleController;
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
    # ==================================== GENDERS ======================================================
    Route::apiResource('genders', GenderController::class)->names('v1.genders');
    # ==================================== TITLES ======================================================
    Route::apiResource('titles', TitleController::class)->names('v1.titles');
    # ==================================== COUNTRIES ======================================================
    Route::apiResource('countries', CountryController::class)->names('v1.countries');
    # ==================================== DISTRICTS ======================================================
    Route::apiResource('districts', DistrictController::class)->names('v1.districts');
});
