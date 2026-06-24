<?php

use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Institution\DepartmentAcademicCalendarController;
use App\Http\Controllers\Api\V1\Institution\DepartmentCourseController;
use App\Http\Controllers\Api\V1\Institution\DepartmentLevelController;
use App\Http\Controllers\Api\V1\Institution\DepartmentLevelCourseController;
use App\Http\Controllers\Api\V1\Institution\DepartmentMetaDataController;
use App\Http\Controllers\Api\V1\Institution\InstitutionDepartmentController;
use App\Http\Controllers\Api\V1\Institution\IntakePeriodController;
use App\Http\Controllers\Api\V1\Institution\ModeOfStudyController;
use App\Http\Controllers\Api\V1\Institution\StudentApplicationDropdownController;
use App\Http\Controllers\Api\V1\Staff\StaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('departments/{institution_department}/courses', [DepartmentMetaDataController::class, 'courses'])->name('v1.department-metadata.courses');
    Route::get('departments/{institution_department}/levels', [DepartmentMetaDataController::class, 'levels'])->name('v1.department-metadata.levels');
    Route::get('departments/{institution_department}/staff', [DepartmentMetaDataController::class, 'staff'])->name('v1.department-metadata.staff');
    Route::get('departments/{institution_department}/workflow-steps', [DepartmentMetaDataController::class, 'workflowSteps'])->name('v1.department-metadata.workflow-steps');
    Route::get('departments/{institution_department}/class-sizes', [DepartmentMetaDataController::class, 'classSizes'])->name('v1.department-metadata.class-sizes');
    Route::get('departments/{institution_department}/enrolments', [DepartmentMetaDataController::class, 'departmentEnrolments'])->name('v1.department-metadata.enrolments');
    Route::get('departments/{institution_department}/class-lists', [DepartmentMetaDataController::class, 'departmentClassLists'])->name('v1.department-metadata.class-lists');
    Route::get('departments/{institution_department}/class-config-course-syllabuses', [DepartmentMetaDataController::class, 'classConfigCourseSyllabuses'])->name('v1.department-metadata.class-config-course-syllabuses');
    Route::post('institution/dashboard/metrics', [DashboardController::class, 'index'])->name('v1.institution.dashboard.metrics');
    // ========================================= ACADEMIC CALENDARS =====================================================
    Route::get('departments/{institution_department}/academic-calendars', [DepartmentAcademicCalendarController::class, 'departmentAcademicCalendar'])->name('v1.departments.academic-calendars');
});
Route::prefix('v1')->group(function () {
    Route::get('institution-departments', [InstitutionDepartmentController::class, 'index'])->name('v1.institution-departments.index');
    Route::get('institution-departments/{institution_department}/levels', [DepartmentLevelController::class, 'index'])->name('v1.department-levels.index');
    Route::get('institution-departments/levels/{department_level}/courses', [DepartmentLevelCourseController::class, 'index'])->name('v1.department-level-courses.index');
    Route::get(
        'institution-departments/{institution_department}/level-courses',
        [DepartmentLevelCourseController::class, 'institutionDepartmentLevelCourses']
    )->name('v1.department-level-courses.by-institution-department');
    Route::get('institution-departments/levels/{department_level}/requirements', [DepartmentLevelController::class, 'levelRequirements'])->name('v1.department-level-requirements');
    Route::get('institution-departments/{department_level}/courses/{department_course}/requirements', [DepartmentCourseController::class, 'courseRequirements'])->name('v1.department-course-requirements');
    Route::apiResource('staff', StaffController::class)->names('v1.staff');
    Route::apiResource('intake-periods', IntakePeriodController::class)->names('v1.intake-periods');
    Route::get('course-modes/{department_course}/course/{department_level}/level', [ModeOfStudyController::class, 'courseModes'])->name('v1.modes-of-study.course-modes');
    Route::apiResource('modes-of-study', ModeOfStudyController::class)->names('v1.modes-of-study');
    Route::get('dropdowns/institution-departments', [StudentApplicationDropdownController::class, 'institutionDepartments'])->name('v1.dropdowns.institution-departments');
    Route::get('dropdowns/institution-departments/{institution_department}/levels', [StudentApplicationDropdownController::class, 'departmentLevels'])->name('v1.dropdowns.institution-departments.levels');
    Route::get('dropdowns/department-levels/{department_level}/courses', [StudentApplicationDropdownController::class, 'departmentCourses'])->name('v1.dropdowns.department-level.courses');
});
