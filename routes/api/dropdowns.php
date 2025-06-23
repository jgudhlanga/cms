<?php

use App\Http\Controllers\Api\V1\Institution\CourseController;
use App\Http\Controllers\Api\V1\Institution\DepartmentController;
use App\Http\Controllers\Api\V1\Institution\LevelController;
use App\Http\Controllers\Api\V1\Shared\AcademicLevelController;
use App\Http\Controllers\Api\V1\Shared\AddressTypeController;
use App\Http\Controllers\Api\V1\Shared\CountryController;
use App\Http\Controllers\Api\V1\Shared\DistrictController;
use App\Http\Controllers\Api\V1\Shared\GenderController;
use App\Http\Controllers\Api\V1\Shared\GradeController;
use App\Http\Controllers\Api\V1\Shared\MaritalStatusController;
use App\Http\Controllers\Api\V1\Shared\ProvinceController;
use App\Http\Controllers\Api\V1\Shared\RelationshipController;
use App\Http\Controllers\Api\V1\Shared\ReligionController;
use App\Http\Controllers\Api\V1\Shared\SponsorTypeController;
use App\Http\Controllers\Api\V1\Shared\SubjectController;
use App\Http\Controllers\Api\V1\Shared\TitleController;
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
    # ==================================== SUBJECTS ======================================================
    Route::apiResource('subjects', SubjectController::class)->names('v1.subjects');
    # ==================================== SUBJECTS ======================================================
    Route::apiResource('grades', GradeController::class)->names('v1.grades');
    # ==================================== MARITAL STATUSES ======================================================
    Route::apiResource('marital-statuses', MaritalStatusController::class)->names('v1.marital-statuses');
    # ==================================== RELATIONSHIPS ======================================================
    Route::apiResource('relationships', RelationshipController::class)->names('v1.relationships');
    # ==================================== RELIGIONS ======================================================
    Route::apiResource('religions', ReligionController::class)->names('v1.religions');
    # ==================================== ACADEMIC LEVELS ======================================================
    Route::apiResource('academic-levels', AcademicLevelController::class)->names('v1.academic-levels');
    # ==================================== SPONSOR TYPE ======================================================
    Route::apiResource('sponsor-types', SponsorTypeController::class)->names('v1.sponsor-types');
});
