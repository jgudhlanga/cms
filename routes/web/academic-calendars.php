<?php

use App\Http\Controllers\AcademicCalendars\AcademicCalendarClassController;
use App\Http\Controllers\AcademicCalendars\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::prefix('institution')->middleware('auth')->group(function () {
    // ===================================== ACADEMIC CALENDARS ==========================================================
    Route::get('academic-calendars', [AcademicCalendarController::class, 'index'])->name('academic-calendars.index');
    Route::post('academic-calendars', [AcademicCalendarController::class, 'store'])->name('academic-calendars.store');
    Route::put('academic-calendars/{academic_calendar}', [AcademicCalendarController::class, 'update'])->name('academic-calendars.update');
    Route::get('academic-calendars/department/{institution_department}/classes/{calendar_year}', [AcademicCalendarController::class, 'departmentAcademicCalendarClasses'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes');
    Route::get('academic-calendars/department/{institution_department}/classes/{calendar_year}/course-work-marksheet', [AcademicCalendarController::class, 'showDepartmentAcademicCalendarClassConfigCourseWorkMarksheet'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.course-work-marksheet');
    Route::get('academic-calendars/department/{institution_department}/classes/{calendar_year}/course-work-marksheet/export', [AcademicCalendarController::class, 'exportDepartmentAcademicCalendarClassConfigCourseWork'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.course-work-marksheet.export');
    Route::get('academic-calendars/department/{institution_department}/classes/{calendar_year}/course-work-import', [AcademicCalendarController::class, 'showDepartmentAcademicCalendarClassConfigCourseWorkImport'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.course-work-import');
    Route::get('academic-calendars/department/{institution_department}/classes/{calendar_year}/course-work-import/template', [AcademicCalendarController::class, 'downloadDepartmentAcademicCalendarClassConfigCourseWorkImportTemplate'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.course-work-import.template');
    Route::post('academic-calendars/department/{institution_department}/classes/{calendar_year}/course-work-import/preview', [AcademicCalendarController::class, 'previewDepartmentAcademicCalendarClassConfigCourseWorkImport'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.course-work-import.preview');
    Route::post('academic-calendars/department/{institution_department}/classes/{calendar_year}/course-work-import', [AcademicCalendarController::class, 'processDepartmentAcademicCalendarClassConfigCourseWorkImport'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.course-work-import.process');
    Route::get('academic-calendars/department/{institution_department}/classes/{calendar_year}/show/{academic_calendar_class}', [AcademicCalendarController::class, 'showDepartmentAcademicCalendarClass'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.show');
    Route::get('academic-calendars/department/{institution_department}/classes/{calendar_year}/show/{academic_calendar_class}/students/{student_enrolment}/course-work', [AcademicCalendarController::class, 'showDepartmentAcademicCalendarClassStudentCourseWork'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.student-course-work');
    Route::patch('academic-calendars/department/{institution_department}/classes/{calendar_year}/show/{academic_calendar_class}', [AcademicCalendarClassController::class, 'update'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.update');
    Route::post('academic-calendars/department/{institution_department}/classes/{calendar_year}/show/{academic_calendar_class}/move-students', [AcademicCalendarController::class, 'moveDepartmentAcademicCalendarClassStudents'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.move-students');
    Route::post('academic-calendars/department/{institution_department}/classes/{calendar_year}', [AcademicCalendarController::class, 'storeDepartmentAcademicCalendarClasses'])->where('calendar_year', '[0-9]{4}')->name('academic-calendars.department-classes.store');
    Route::post('academic-calendars/{institution_department}/classes-config', [AcademicCalendarController::class, 'update'])->name('academic-calendars.classes-config.store');
    Route::post('academic-calendars/{institution_department}/classes-config/{academic_calendar}', [AcademicCalendarController::class, 'storePerClassSizeConfig'])->name('academic-calendars.classes-config.per-class-size.store');
});
