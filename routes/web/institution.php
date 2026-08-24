<?php

use App\Http\Controllers\AcademicCalendars\SemesterController;
use App\Http\Controllers\Institution\Config\AssessmentCalendarController;
use App\Http\Controllers\Institution\Config\AssessmentTypeController;
use App\Http\Controllers\Institution\Config\FeeStructureController;
use App\Http\Controllers\Institution\Config\InstitutionConfigController;
use App\Http\Controllers\Institution\Config\InstitutionFeatureController;
use App\Http\Controllers\Institution\Config\IntakePeriodController;
use App\Http\Controllers\Institution\Departments\CourseSyllabusController;
use App\Http\Controllers\Institution\Departments\CourseSyllabusModuleController;
use App\Http\Controllers\Institution\Departments\DepartmentClassSizeController;
use App\Http\Controllers\Institution\Departments\DepartmentCourseController;
use App\Http\Controllers\Institution\Departments\DepartmentLevelController;
use App\Http\Controllers\Institution\Departments\InstitutionDepartmentController;
use App\Http\Controllers\Institution\DocumentTemplates\DocumentTemplateController;
use App\Http\Controllers\Institution\Dropdowns\CourseController;
use App\Http\Controllers\Institution\Dropdowns\DepartmentController;
use App\Http\Controllers\Institution\Dropdowns\DivisionController;
use App\Http\Controllers\Institution\Dropdowns\GradeController;
use App\Http\Controllers\Institution\Dropdowns\LevelController;
use App\Http\Controllers\Institution\Dropdowns\ModeOfStudyController;
use App\Http\Controllers\Institution\Dropdowns\SubjectController;
use App\Http\Controllers\Institution\Enrolments\ApplicationOfferingController;
use App\Http\Controllers\Institution\InstitutionController;
use App\Http\Controllers\Institution\Staff\StaffController;
use App\Http\Controllers\Students\StudentEnrolmentStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('institution')->middleware('auth')->group(function () {
    Route::get('/', InstitutionController::class)->name('institution.index');
    // ==================================== DEPARTMENTS =================================================================
    Route::post('departments/sync-institution-department', [InstitutionDepartmentController::class, 'syncInstitutionDepartment'])->name('institution-departments.sync');
    Route::put('departments/{department}/restore', [InstitutionDepartmentController::class, 'restore'])->name('institution-departments.restore');
    Route::delete('departments/{department}/force-delete', [InstitutionDepartmentController::class, 'forceDelete'])->name('institution-departments.force-delete');
    Route::resource('departments', InstitutionDepartmentController::class)->names('institution-departments');
    // ==================================== DEPARTMENT LEVELS ===========================================================
    Route::post('departments/{institution_department}/sync-levels', [DepartmentLevelController::class, 'syncDepartmentLevels'])->name('department-levels.sync');
    Route::get('departments/{department_level}/requirements', [DepartmentLevelController::class, 'departmentLevelRequirements'])->name('department-levels.requirements');
    Route::post('departments/{department_level}/requirements', [DepartmentLevelController::class, 'updateDepartmentLevelRequirements'])->name('department-levels.store-requirements');
    Route::get('departments/{institution_department}/enrolments/{department_level}', [DepartmentLevelController::class, 'enrolments'])->name('department-levels.enrolments');
    // ==================================== DEPARTMENT COURSES ==========================================================
    Route::post('departments/{institution_department}/sync-courses', [DepartmentCourseController::class, 'syncDepartmentCourses'])->name('department-courses.sync');
    Route::get('departments/{department_course}/show', [DepartmentCourseController::class, 'show'])->name('department-courses.show');
    Route::get('departments/{department_course}/course-requirements', [DepartmentCourseController::class, 'courseRequirements'])->name('department-courses.requirements');
    Route::post('departments/{department_course}/course-requirements', [DepartmentCourseController::class, 'updateCourseRequirements'])->name('department-courses.store-requirements');
    Route::post('departments/{department_course}/update', [DepartmentCourseController::class, 'update'])->name('department-courses.update');
    Route::get('departments/course/{department_course}/modes', [DepartmentCourseController::class, 'courseLevelModes'])->name('department-courses.modes');
    Route::post('departments/course/{department_course}/modes', [DepartmentCourseController::class, 'storeCourseLevelModes'])->name('department-courses.modes.store');
    // ==================================== COURSE SYLLABUSES ==========================================================
    Route::get('departments/{institution_department}/course-syllabuses', [CourseSyllabusController::class, 'index'])->name('department-course-syllabuses.index');
    Route::get('departments/{institution_department}/course-syllabuses/import', [CourseSyllabusController::class, 'showImport'])->name('department-course-syllabuses.import');
    Route::get('departments/{institution_department}/course-syllabuses/import/template', [CourseSyllabusController::class, 'downloadImportTemplate'])->name('department-course-syllabuses.import.template');
    Route::post('departments/{institution_department}/course-syllabuses/import/preview', [CourseSyllabusController::class, 'previewImport'])->name('department-course-syllabuses.import.preview');
    Route::post('departments/{institution_department}/course-syllabuses/import', [CourseSyllabusController::class, 'processImport'])->name('department-course-syllabuses.import.process');
    Route::get('departments/{institution_department}/course-syllabuses/create', [CourseSyllabusController::class, 'create'])->name('department-course-syllabuses.create');
    Route::get('departments/{institution_department}/course-syllabuses/{course_syllabus}/show', [CourseSyllabusController::class, 'show'])->name('department-course-syllabuses.show');
    Route::get('departments/{institution_department}/course-syllabuses/{course_syllabus}/edit', [CourseSyllabusController::class, 'edit'])->name('department-course-syllabuses.edit');
    Route::get(
        'departments/{institution_department}/course-syllabuses/{course_syllabus}/syllabus-document',
        [CourseSyllabusController::class, 'syllabusDocument']
    )->name('department-course-syllabuses.syllabus');
    Route::post('departments/course-syllabuses', [CourseSyllabusController::class, 'store'])->name('department-course-syllabuses.store');
    Route::put('departments/course-syllabuses/{course_syllabus}', [CourseSyllabusController::class, 'update'])->name('department-course-syllabuses.update');
    Route::delete('departments/course-syllabuses/{course_syllabus}', [CourseSyllabusController::class, 'destroy'])->name('department-course-syllabuses.destroy');
    Route::get(
        'departments/{institution_department}/course-syllabuses/{course_syllabus}/modules',
        [CourseSyllabusModuleController::class, 'index']
    )->name('course-syllabus-modules.index');
    Route::post('departments/course-syllabus-modules', [CourseSyllabusModuleController::class, 'store'])->name('course-syllabus-modules.store');
    Route::put(
        'departments/course-syllabus-modules/{course_syllabus_module}',
        [CourseSyllabusModuleController::class, 'update']
    )->name('course-syllabus-modules.update');
    Route::post(
        'departments/{institution_department}/course-syllabuses/{course_syllabus}/modules/move',
        [CourseSyllabusModuleController::class, 'moveModules']
    )->name('course-syllabus-modules.move');
    // ==================================== INTAKE PERIODS ==============================================================
    Route::put('intake-periods/{intake_period}/restore', [IntakePeriodController::class, 'restore'])->name('intake-periods.restore');
    Route::delete('intake-periods/{intake_period}/force-delete', [IntakePeriodController::class, 'forceDelete'])->name('intake-periods.force-delete');
    Route::resource('intake-periods', IntakePeriodController::class)->names('intake-periods');
    // ==================================== ASSESSMENT TYPES ==============================================================
    Route::put('assessment-types/{assessment_type}/restore', [AssessmentTypeController::class, 'restore'])->name('assessment-types.restore');
    Route::delete('assessment-types/{assessment_type}/force-delete', [AssessmentTypeController::class, 'forceDelete'])->name('assessment-types.force-delete');
    Route::resource('assessment-types', AssessmentTypeController::class)->names('assessment-types');
    Route::put('assessment-types/{assessment_type}/calendars/{calendar}/restore', [AssessmentCalendarController::class, 'restore'])->name('assessment-calendars.restore');
    Route::delete('assessment-types/{assessment_type}/calendars/{calendar}/force-delete', [AssessmentCalendarController::class, 'forceDelete'])->name('assessment-calendars.force-delete');
    Route::resource('assessment-types.calendars', AssessmentCalendarController::class)->names('assessment-calendars')->scoped();
    // ==================================== DOCUMENT TEMPLATES ==============================================================
    Route::get('document-templates/{document_template}/preview', [DocumentTemplateController::class, 'preview'])->name('document-templates.preview');
    Route::put('document-templates/{document_template}/restore', [DocumentTemplateController::class, 'restore'])->name('document-templates.restore');
    Route::delete('document-templates/{document_template}/force-delete', [DocumentTemplateController::class, 'forceDelete'])->name('document-templates.force-delete');
    Route::resource('document-templates', DocumentTemplateController::class)->names('document-templates');
    // ==================================== FEE STRUCTURE ==============================================================
    Route::put('fee-structures/{fee_structure}/restore', [FeeStructureController::class, 'restore'])->name('fee-structures.restore');
    Route::delete('fee-structures/{fee_structure}/force-delete', [FeeStructureController::class, 'forceDelete'])->name('fee-structures.force-delete');
    Route::resource('fee-structures', FeeStructureController::class)->names('fee-structures');
    // ==================================== APPLICATION OFFERINGS (online enrolments catalogue) ========================
    Route::get('enrolments', [ApplicationOfferingController::class, 'index'])->name('application-offerings.index');
    Route::get('enrolments/{institution_department}', [ApplicationOfferingController::class, 'show'])->name('application-offerings.show');
    Route::put('enrolments/{institution_department}', [ApplicationOfferingController::class, 'update'])->name('application-offerings.update');
    // ==================================== INSTITUTION FEATURES =======================================================
    Route::get('features', [InstitutionFeatureController::class, 'index'])->name('institution-features.index');
    Route::put('features', [InstitutionFeatureController::class, 'update'])->name('institution-features.update');
    // ==================================== DEPARTMENT STAFF ============================================================
    Route::put('staff/{staff}/restore', [StaffController::class, 'restore'])->name('staff.restore');
    Route::delete('staff/{staff}/force-delete', [StaffController::class, 'forceDelete'])->name('staff.force-delete');
    Route::resource('departments.staff', StaffController::class)->names('staff');
    // ====================================== DEPARTMENT COURSE & CLASS CONFIG =====================================================
    Route::post('{institution_department}/class-sizes', [DepartmentClassSizeController::class, 'store'])->name('class-sizes.store');
    Route::put('{institution_department}/class-sizes', [DepartmentClassSizeController::class, 'update'])->name('class-sizes.update');
    // ============================================= INSTITUTION SETUP =======================================================
    Route::prefix('config')->group(function () {
        Route::get('/', [InstitutionConfigController::class, 'index'])->name('institution.setup');
        // ==================================== COURSES ======================================================
        Route::put('courses/{course}/move-position', [CourseController::class, 'movePosition'])->name('courses.move-position');
        Route::put('courses/{course}/restore', [CourseController::class, 'restore'])->name('courses.restore');
        Route::delete('courses/{course}/force-delete', [CourseController::class, 'forceDelete'])->name('courses.force-delete');
        Route::resource('courses', CourseController::class)->names('courses');
        // ==================================== DEPARTMENTS (catalog) ======================================================
        Route::put('departments/{department}/move-position', [DepartmentController::class, 'movePosition'])->name('departments.move-position');
        Route::put('departments/{department}/restore', [DepartmentController::class, 'restore'])->name('departments.restore');
        Route::delete('departments/{department}/force-delete', [DepartmentController::class, 'forceDelete'])->name('departments.force-delete');
        Route::resource('departments', DepartmentController::class)->names('departments');
        // ==================================== DIVISIONS ======================================================
        Route::put('divisions/{division}/move-position', [DivisionController::class, 'movePosition'])->name('divisions.move-position');
        Route::put('divisions/{division}/restore', [DivisionController::class, 'restore'])->name('divisions.restore');
        Route::delete('divisions/{division}/force-delete', [DivisionController::class, 'forceDelete'])->name('divisions.force-delete');
        Route::resource('divisions', DivisionController::class)->names('divisions');
        // ==================================== GRADES ======================================================
        Route::put('grades/{grade}/move-position', [GradeController::class, 'movePosition'])->name('grades.move-position');
        Route::put('grades/{grade}/restore', [GradeController::class, 'restore'])->name('grades.restore');
        Route::delete('grades/{grade}/force-delete', [GradeController::class, 'forceDelete'])->name('grades.force-delete');
        Route::resource('grades', GradeController::class)->names('grades');
        // ==================================== LEVELS ======================================================
        Route::put('levels/{level}/move-position', [LevelController::class, 'movePosition'])->name('levels.move-position');
        Route::put('levels/{level}/restore', [LevelController::class, 'restore'])->name('levels.restore');
        Route::delete('levels/{level}/force-delete', [LevelController::class, 'forceDelete'])->name('levels.force-delete');
        Route::resource('levels', LevelController::class)->names('levels');
        // ==================================== MODES OF STUDY ======================================================
        Route::put('mode-of-studies/{mode_of_study}/restore', [ModeOfStudyController::class, 'restore'])->name('mode-of-studies.restore');
        Route::delete('mode-of-studies/{mode_of_study}/force-delete', [ModeOfStudyController::class, 'forceDelete'])->name('mode-of-studies.force-delete');
        Route::resource('mode-of-studies', ModeOfStudyController::class)->names('mode-of-studies');
        // ==================================== SUBJECTS ======================================================
        Route::put('subjects/{subject}/move-position', [SubjectController::class, 'movePosition'])->name('subjects.move-position');
        Route::put('subjects/{subject}/restore', [SubjectController::class, 'restore'])->name('subjects.restore');
        Route::delete('subjects/{subject}/force-delete', [SubjectController::class, 'forceDelete'])->name('subjects.force-delete');
        Route::resource('subjects', SubjectController::class)->names('subjects');
        // ==================================== STUDENT ENROLMENT STATUSES ======================================================
        Route::put('student-enrolment-statuses/{student_enrolment_status}/restore', [StudentEnrolmentStatusController::class, 'restore'])->name('student-enrolment-statuses.restore');
        Route::delete('student-enrolment-statuses/{student_enrolment_status}/force-delete', [StudentEnrolmentStatusController::class, 'forceDelete'])->name('student-enrolment-statuses.force-delete');
        Route::resource('student-enrolment-statuses', StudentEnrolmentStatusController::class)->names('student-enrolment-statuses');
        // ==================================== ACADEMIC YEAR OPTIONS ======================================================
        Route::put('semesters/{semester}/restore', [SemesterController::class, 'restore'])->name('semesters.restore');
        Route::delete('semesters/{semester}/force-delete', [SemesterController::class, 'forceDelete'])->name('semesters.force-delete');
        Route::resource('semesters', SemesterController::class)->names('semesters');
    });
});
