<?php

use App\Http\Controllers\Institution\Config\FeeStructureController;
use App\Http\Controllers\Institution\Departments\DepartmentApplicationStepController;
use App\Http\Controllers\Institution\Departments\DepartmentClassSizeController;
use App\Http\Controllers\Institution\Departments\DepartmentCourseController;
use App\Http\Controllers\Institution\Departments\DepartmentLevelController;
use App\Http\Controllers\Institution\Departments\InstitutionDepartmentController;
use App\Http\Controllers\Institution\DocumentTemplates\DocumentTemplateController;
use App\Http\Controllers\Institution\InstitutionController;
use App\Http\Controllers\Institution\Config\IntakePeriodController;
use App\Http\Controllers\Institution\Config\InstitutionConfigController;
use App\Http\Controllers\Institution\Staff\StaffController;
use Illuminate\Support\Facades\Route;


Route::prefix('institution')->middleware('auth')->group(function () {
    Route::get('/', InstitutionController::class)->name('institution.index');
    # ==================================== DEPARTMENTS =================================================================
    Route::post('departments/sync-institution-department', [InstitutionDepartmentController::class, 'syncInstitutionDepartment'])->name('institution-departments.sync');
    Route::put('departments/{department}/restore', [InstitutionDepartmentController::class, 'restore'])->name('institution-departments.restore');
    Route::delete('departments/{department}/force-delete', [InstitutionDepartmentController::class, 'forceDelete'])->name('institution-departments.force-delete');
    Route::resource('departments', InstitutionDepartmentController::class)->names('institution-departments');
    # ==================================== DEPARTMENT LEVELS ===========================================================
    Route::post('departments/{institution_department}/sync-levels', [DepartmentLevelController::class, 'syncDepartmentLevels'])->name('department-levels.sync');
    Route::get('departments/{department_level}/requirements', [DepartmentLevelController::class, 'departmentLevelRequirements'])->name('department-levels.requirements');
    Route::post('departments/{department_level}/requirements', [DepartmentLevelController::class, 'updateDepartmentLevelRequirements'])->name('department-levels.store-requirements');
    Route::get('departments/{institution_department}/enrolments/{department_level}', [DepartmentLevelController::class, 'enrolments'])->name('department-levels.enrolments');
    # ==================================== DEPARTMENT COURSES ==========================================================
    Route::post('departments/{institution_department}/sync-courses', [DepartmentCourseController::class, 'syncDepartmentCourses'])->name('department-courses.sync');
    Route::get('departments/{department_course}/show', [DepartmentCourseController::class, 'show'])->name('department-courses.show');
    Route::post('departments/{department_course}/update', [DepartmentCourseController::class, 'update'])->name('department-courses.update');
    # ==================================== DEPARTMENT APPLICATION STEPS ================================================
    Route::post('departments/{institution_department}/sync-application-steps', [DepartmentApplicationStepController::class, 'syncApplicationSteps'])->name('department-application-steps.sync');
    Route::get('departments/{department_application_step}/application-steps/show', [DepartmentApplicationStepController::class, 'show'])->name('department-application-steps.show');
    Route::post('departments/{department_application_step}/application-steps/update', [DepartmentApplicationStepController::class, 'update'])->name('department-application-steps.update');
    Route::post('departments/{institution_department}/sync-application-step-metadata', [DepartmentApplicationStepController::class, 'syncWorkflowStepActionMetadata'])->name('department-application-steps.sync-metadata');
    # ==================================== INTAKE PERIODS ==============================================================
    Route::put('intake-periods/{intake_period}/restore', [IntakePeriodController::class, 'restore'])->name('intake-periods.restore');
    Route::delete('intake-periods/{intake_period}/force-delete', [IntakePeriodController::class, 'forceDelete'])->name('intake-periods.force-delete');
    Route::resource('intake-periods', IntakePeriodController::class)->names('intake-periods');
    # ==================================== DOCUMENT TEMPLATES ==============================================================
    Route::get('document-templates/{document_template}/preview', [DocumentTemplateController::class, 'preview'])->name('document-templates.preview');
    Route::put('document-templates/{document_template}/restore', [DocumentTemplateController::class, 'restore'])->name('document-templates.restore');
    Route::delete('document-templates/{document_template}/force-delete', [DocumentTemplateController::class, 'forceDelete'])->name('document-templates.force-delete');
    Route::resource('document-templates', DocumentTemplateController::class)->names('document-templates');
    # ==================================== FEE STRUCTURE ==============================================================
    Route::put('fee-structures/{fee_structure}/restore', [FeeStructureController::class, 'restore'])->name('fee-structures.restore');
    Route::delete('fee-structures/{fee_structure}/force-delete', [FeeStructureController::class, 'forceDelete'])->name('fee-structures.force-delete');
    Route::resource('fee-structures', FeeStructureController::class)->names('fee-structures');
    # ==================================== DEPARTMENT STAFF ============================================================
    Route::put('staff/{staff}/restore', [StaffController::class, 'restore'])->name('staff.restore');
    Route::delete('staff/{staff}/force-delete', [StaffController::class, 'forceDelete'])->name('staff.force-delete');
    Route::resource('departments.staff', StaffController::class)->names('staff');
    #====================================== DEPARTMENT CLASS SIZES =====================================================
    Route::post('{institution_department}/class-sizes', [DepartmentClassSizeController::class, 'store'])->name('class-sizes.store');
    # ============================================= INSTITUTION SETUP =======================================================
    Route::prefix('config')->group(function () {
        Route::get('/', [InstitutionConfigController::class, 'index'])->name('institution.setup');
    });
});

