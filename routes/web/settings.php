<?php

use App\Http\Controllers\Institution\Dropdowns\CourseController;
use App\Http\Controllers\Institution\Dropdowns\DepartmentController;
use App\Http\Controllers\Institution\Dropdowns\DivisionController;
use App\Http\Controllers\Institution\Dropdowns\GradeController;
use App\Http\Controllers\Institution\Dropdowns\LevelController;
use App\Http\Controllers\Institution\Dropdowns\ModeOfStudyController;
use App\Http\Controllers\Institution\Dropdowns\SubjectController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Shared\AcademicLevelController;
use App\Http\Controllers\Shared\AddressTypeController;
use App\Http\Controllers\Shared\ApplicationStepController;
use App\Http\Controllers\Shared\CommunicationMethodController;
use App\Http\Controllers\Shared\CountryController;
use App\Http\Controllers\Shared\DistrictController;
use App\Http\Controllers\Shared\EmploymentTypeController;
use App\Http\Controllers\Shared\GenderController;
use App\Http\Controllers\Shared\LanguageController;
use App\Http\Controllers\Shared\MaritalStatusController;
use App\Http\Controllers\Shared\ProvinceController;
use App\Http\Controllers\Shared\RaceController;
use App\Http\Controllers\Shared\RelationshipController;
use App\Http\Controllers\Shared\ReligionController;
use App\Http\Controllers\Shared\SponsorTypeController;
use App\Http\Controllers\Shared\StatusController;
use App\Http\Controllers\Shared\TitleController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->middleware('auth')->group(function () {
    Route::get('/', SettingsController::class)->name('settings.index');
    # ==================================== COMMUNICATIONS ======================================================
    Route::put('communication-methods/{communication_method}/restore', [CommunicationMethodController::class, 'restore'])->name('communication-methods.restore');
    Route::delete('communication-methods/{communication_method}/force-delete', [CommunicationMethodController::class, 'forceDelete'])->name('communication-methods.force-delete');
    Route::resource('communication-methods', CommunicationMethodController::class)->names('communication-methods');
    # ==================================== COUNTRIES ======================================================
    Route::put('countries/{country}/restore', [CountryController::class, 'restore'])->name('countries.restore');
    Route::delete('countries/{country}/force-delete', [CountryController::class, 'forceDelete'])->name('countries.force-delete');
    Route::resource('countries', CountryController::class)->names('countries');
    # ==================================== GENDERS ======================================================
    Route::put('genders/{gender}/restore', [GenderController::class, 'restore'])->name('genders.restore');
    Route::delete('genders/{gender}/force-delete', [GenderController::class, 'forceDelete'])->name('genders.force-delete');
    Route::resource('genders', GenderController::class)->names('genders');
    # ==================================== LANGUAGES ======================================================
    Route::put('languages/{language}/restore', [LanguageController::class, 'restore'])->name('languages.restore');
    Route::delete('languages/{language}/force-delete', [LanguageController::class, 'forceDelete'])->name('languages.force-delete');
    Route::resource('languages', LanguageController::class)->names('languages');
    # ==================================== PROVINCES ======================================================
    Route::put('provinces/{province}/restore', [ProvinceController::class, 'restore'])->name('provinces.restore');
    Route::delete('provinces/{province}/force-delete', [ProvinceController::class, 'forceDelete'])->name('provinces.force-delete');
    Route::resource('provinces', ProvinceController::class)->names('provinces');
    # ==================================== RACES ======================================================
    Route::put('races/{race}/restore', [RaceController::class, 'restore'])->name('races.restore');
    Route::delete('races/{race}/force-delete', [RaceController::class, 'forceDelete'])->name('races.force-delete');
    Route::resource('races', RaceController::class)->names('races');
    # ==================================== STATUSES ======================================================
    Route::put('statuses/{status}/restore', [StatusController::class, 'restore'])->name('statuses.restore');
    Route::delete('statuses/{status}/force-delete', [StatusController::class, 'forceDelete'])->name('statuses.force-delete');
    Route::resource('statuses', StatusController::class)->names('statuses');
    # ==================================== MARITAL STATUSES ======================================================
    Route::put('marital-statuses/{marital_status}/restore', [MaritalStatusController::class, 'restore'])->name('marital-statuses.restore');
    Route::delete('marital-statuses/{marital_status}/force-delete', [MaritalStatusController::class, 'forceDelete'])->name('marital-statuses.force-delete');
    Route::resource('marital-statuses', MaritalStatusController::class)->names('marital-statuses');
    # ==================================== TITLES ======================================================
    Route::put('titles/{title}/restore', [TitleController::class, 'restore'])->name('titles.restore');
    Route::delete('titles/{title}/force-delete', [TitleController::class, 'forceDelete'])->name('titles.force-delete');
    Route::resource('titles', TitleController::class)->names('titles');
    # ==================================== RELATIONSHIPS ======================================================
    Route::put('relationships/{relationship}/restore', [RelationshipController::class, 'restore'])->name('relationships.restore');
    Route::delete('relationships/{relationship}/force-delete', [RelationshipController::class, 'forceDelete'])->name('relationships.force-delete');
    Route::resource('relationships', RelationshipController::class)->names('relationships');
    # ==================================== ADDRESS TYPES ======================================================
    Route::put('address-types/{address_type}/restore', [AddressTypeController::class, 'restore'])->name('address-types.restore');
    Route::delete('address-types/{address_type}/force-delete', [AddressTypeController::class, 'forceDelete'])->name('address-types.force-delete');
    Route::resource('address-types', AddressTypeController::class)->names('address-types');
    # ==================================== DISTRICTS ======================================================
    Route::put('districts/{district}/restore', [DistrictController::class, 'restore'])->name('districts.restore');
    Route::delete('districts/{district}/force-delete', [DistrictController::class, 'forceDelete'])->name('districts.force-delete');
    Route::resource('districts', DistrictController::class)->names('districts');

    # ********************************************* INSTITUTION SPECIFIC ********************************
    # ==================================== COURSES ======================================================
    Route::put('courses/{course}/move-position', [CourseController::class, 'movePosition'])->name('courses.move-position');
    Route::put('courses/{course}/restore', [CourseController::class, 'restore'])->name('courses.restore');
    Route::delete('courses/{course}/force-delete', [CourseController::class, 'forceDelete'])->name('courses.force-delete');
    Route::resource('courses', CourseController::class)->names('courses');
    # ==================================== DEPARTMENTS ======================================================
    Route::put('departments/{department}/move-position', [DepartmentController::class, 'movePosition'])->name('departments.move-position');
    Route::put('departments/{department}/restore', [DepartmentController::class, 'restore'])->name('departments.restore');
    Route::delete('departments/{department}/force-delete', [DepartmentController::class, 'forceDelete'])->name('departments.force-delete');
    Route::resource('departments', DepartmentController::class)->names('departments');
    # ==================================== DIVISIONS ======================================================
    Route::put('divisions/{division}/move-position', [DivisionController::class, 'movePosition'])->name('divisions.move-position');
    Route::put('divisions/{division}/restore', [DivisionController::class, 'restore'])->name('divisions.restore');
    Route::delete('divisions/{division}/force-delete', [DivisionController::class, 'forceDelete'])->name('divisions.force-delete');
    Route::resource('divisions', DivisionController::class)->names('divisions');
    # ==================================== GRADES ======================================================
    Route::put('grades/{grade}/move-position', [GradeController::class, 'movePosition'])->name('grades.move-position');
    Route::put('grades/{grade}/restore', [GradeController::class, 'restore'])->name('grades.restore');
    Route::delete('grades/{grade}/force-delete', [GradeController::class, 'forceDelete'])->name('grades.force-delete');
    Route::resource('grades', GradeController::class)->names('grades');
    # ==================================== LEVELS ======================================================
    Route::put('levels/{level}/move-position', [LevelController::class, 'movePosition'])->name('levels.move-position');
    Route::put('levels/{level}/restore', [LevelController::class, 'restore'])->name('levels.restore');
    Route::delete('levels/{level}/force-delete', [LevelController::class, 'forceDelete'])->name('levels.force-delete');
    Route::resource('levels', LevelController::class)->names('levels');
    # ==================================== MODES OF STUDY ======================================================
    Route::put('mode-of-studies/{mode_of_study}/restore', [ModeOfStudyController::class, 'restore'])->name('mode-of-studies.restore');
    Route::delete('mode-of-studies/{mode_of_study}/force-delete', [ModeOfStudyController::class, 'forceDelete'])->name('mode-of-studies.force-delete');
    Route::resource('mode-of-studies', ModeOfStudyController::class)->names('mode-of-studies');
    # ==================================== SUBJECTS ======================================================
    Route::put('subjects/{subject}/move-position', [SubjectController::class, 'movePosition'])->name('subjects.move-position');
    Route::put('subjects/{subject}/restore', [SubjectController::class, 'restore'])->name('subjects.restore');
    Route::delete('subjects/{subject}/force-delete', [SubjectController::class, 'forceDelete'])->name('subjects.force-delete');
    Route::resource('subjects', SubjectController::class)->names('subjects');
    # ==================================== RELIGIONS ======================================================
    Route::put('religions/{religion}/restore', [ReligionController::class, 'restore'])->name('religions.restore');
    Route::delete('religions/{religion}/force-delete', [ReligionController::class, 'forceDelete'])->name('religions.force-delete');
    Route::resource('religions', ReligionController::class)->names('religions');
    # ==================================== ACADEMIC LEVELS ======================================================
    Route::put('academic-levels/{academic_level}/restore', [AcademicLevelController::class, 'restore'])->name('academic-levels.restore');
    Route::delete('academic-levels/{academic_level}/force-delete', [AcademicLevelController::class, 'forceDelete'])->name('academic-levels.force-delete');
    Route::resource('academic-levels', AcademicLevelController::class)->names('academic-levels');
    # ==================================== SPONSOR TYPES ======================================================
    Route::put('sponsor-types/{sponsor_type}/restore', [SponsorTypeController::class, 'restore'])->name('sponsor-types.restore');
    Route::delete('sponsor-types/{sponsor_type}/force-delete', [SponsorTypeController::class, 'forceDelete'])->name('sponsor-types.force-delete');
    Route::resource('sponsor-types', SponsorTypeController::class)->names('sponsor-types');
    # ==================================== APPLICATION STEPS ======================================================
    Route::put('application-steps/{application_step}/move-position', [ApplicationStepController::class, 'movePosition'])->name('application-steps.move-position');
    Route::put('application-steps/{application_step}/restore', [ApplicationStepController::class, 'restore'])->name('application-steps.restore');
    Route::delete('application-steps/{application_step}/force-delete', [ApplicationStepController::class, 'forceDelete'])->name('application-steps.force-delete');
    Route::resource('application-steps', ApplicationStepController::class)->names('application-steps');
    # ==================================== EMPLOYMENT TYPES ======================================================
    Route::put('employment-types/{employment_type}/restore', [EmploymentTypeController::class, 'restore'])->name('employment-types.restore');
    Route::delete('employment-types/{employment_type}/force-delete', [EmploymentTypeController::class, 'forceDelete'])->name('employment-types.force-delete');
    Route::resource('employment-types', EmploymentTypeController::class)->names('employment-types');
});
