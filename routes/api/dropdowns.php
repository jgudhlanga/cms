<?php

use App\Http\Controllers\Api\V1\AcademicCalendars\AcademicYearOptionController;
use App\Http\Controllers\Api\V1\Institution\CourseController;
use App\Http\Controllers\Api\V1\Institution\DepartmentController;
use App\Http\Controllers\Api\V1\Institution\LevelController;
use App\Http\Controllers\Api\V1\Shared\AcademicLevelController;
use App\Http\Controllers\Api\V1\Shared\AddressTypeController;
use App\Http\Controllers\Api\V1\Shared\CountryController;
use App\Http\Controllers\Api\V1\Shared\DistrictController;
use App\Http\Controllers\Api\V1\Shared\DocumentTypeController;
use App\Http\Controllers\Api\V1\Shared\EmploymentTypeController;
use App\Http\Controllers\Api\V1\Shared\FeeTypeController;
use App\Http\Controllers\Api\V1\Shared\GenderController;
use App\Http\Controllers\Api\V1\Shared\GradeController;
use App\Http\Controllers\Api\V1\Shared\IdTypeController;
use App\Http\Controllers\Api\V1\Shared\MaritalStatusController;
use App\Http\Controllers\Api\V1\Shared\ProvinceController;
use App\Http\Controllers\Api\V1\Shared\RaceController;
use App\Http\Controllers\Api\V1\Shared\RelationshipController;
use App\Http\Controllers\Api\V1\Shared\ReligionController;
use App\Http\Controllers\Api\V1\Shared\SponsorTypeController;
use App\Http\Controllers\Api\V1\Shared\SubjectController;
use App\Http\Controllers\Api\V1\Shared\TitleController;
use App\Http\Controllers\Api\V1\Shared\WorkflowStepActionController;
use App\Http\Controllers\Api\V1\Shared\WorkflowStepController;
use App\Http\Controllers\Api\V1\Students\StudentEnrolmentStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ==================================== ADDRESS TYPES =================================================
    Route::apiResource('address-types', AddressTypeController::class)->names('v1.address-types');
    // ==================================== PROVINCES ======================================================
    Route::apiResource('provinces', ProvinceController::class)->names('v1.provinces');
    // ==================================== DEPARTMENTS ======================================================
    Route::apiResource('departments', DepartmentController::class)->names('v1.departments');
    // ==================================== LEVELS ======================================================
    Route::apiResource('levels', LevelController::class)->names('v1.levels');
    // ==================================== COURSES ======================================================
    Route::apiResource('courses', CourseController::class)->names('v1.courses');
    // ==================================== GENDERS ======================================================
    Route::apiResource('genders', GenderController::class)->names('v1.genders');
    // ==================================== TITLES ======================================================
    Route::apiResource('titles', TitleController::class)->names('v1.titles');
    // ==================================== COUNTRIES ======================================================
    Route::apiResource('countries', CountryController::class)->names('v1.countries');
    // ==================================== DISTRICTS ======================================================
    Route::apiResource('districts', DistrictController::class)->names('v1.districts');
    // ==================================== SUBJECTS ======================================================
    Route::apiResource('subjects', SubjectController::class)->names('v1.subjects');
    // ==================================== SUBJECTS ======================================================
    Route::apiResource('grades', GradeController::class)->names('v1.grades');
    // ==================================== MARITAL STATUSES ======================================================
    Route::apiResource('marital-statuses', MaritalStatusController::class)->names('v1.marital-statuses');
    // ==================================== RACES ======================================================
    Route::apiResource('races', RaceController::class)->names('v1.races');
    // ==================================== RELATIONSHIPS ======================================================
    Route::apiResource('relationships', RelationshipController::class)->names('v1.relationships');
    // ==================================== RELIGIONS ======================================================
    Route::apiResource('religions', ReligionController::class)->names('v1.religions');
    // ==================================== ACADEMIC LEVELS ======================================================
    Route::apiResource('academic-levels', AcademicLevelController::class)->names('v1.academic-levels');
    // ==================================== SPONSOR TYPE ======================================================
    Route::apiResource('sponsor-types', SponsorTypeController::class)->names('v1.sponsor-types');
    // ==================================== EMPLOYMENT TYPE ======================================================
    Route::apiResource('employment-types', EmploymentTypeController::class)->names('v1.employment-types');
    // ==================================== ID TYPE ======================================================
    Route::apiResource('id-types', IdTypeController::class)->names('v1.id-types');
    // ==================================== WORKFLOW STEPS ======================================================
    Route::apiResource('workflow-steps', WorkflowStepController::class)->names('v1.workflow-steps');
    Route::apiResource('workflow-step-actions', WorkflowStepActionController::class)->names('v1.workflow-step-actions');
    // ==================================== DOCUMENT TYPE ======================================================
    Route::apiResource('document-types', DocumentTypeController::class)->names('v1.document-types');
    // ==================================== FEE TYPE ======================================================
    Route::apiResource('fee-types', FeeTypeController::class)->names('v1.fee-types');
    // ==================================== STUDENT ENROLMENT STATUS ======================================================
    Route::apiResource('student-enrolment-statuses', StudentEnrolmentStatusController::class)->names('v1.student-enrolment-statuses');
    // ==================================== ACADEMIC YEAR OPTIONS ======================================================
    Route::apiResource('academic-year-options', AcademicYearOptionController::class)->names('v1.academic-year-options');
});
