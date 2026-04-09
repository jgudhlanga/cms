<?php

use App\Http\Controllers\AcademicCalendars\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::prefix('institution')->middleware('auth')->group(function () {
    // ===================================== ACADEMIC CALENDARS ==========================================================
    Route::get('academic-calendars', [AcademicCalendarController::class, 'index'])->name('academic-calendars.index');
    Route::post('academic-calendars', [AcademicCalendarController::class, 'store'])->name('academic-calendars.store');
    Route::put('academic-calendars/{academic_calendar}', [AcademicCalendarController::class, 'update'])->name('academic-calendars.update');
    Route::get('academic-calendars/department/{institution_department}/classes/{academic_calendar}', [AcademicCalendarController::class, 'departmentAcademicCalendarClasses'])->name('academic-calendars.department-classes');
    Route::get('academic-calendars/department/{institution_department}/classes/{academic_calendar}/show/{academic_calendar_class}', [AcademicCalendarController::class, 'showDepartmentAcademicCalendarClass'])->name('academic-calendars.department-classes.show');
    Route::post('academic-calendars/department/{institution_department}/classes/{academic_calendar}', [AcademicCalendarController::class, 'storeDepartmentAcademicCalendarClasses'])->name('academic-calendars.department-classes.store');
    Route::post('academic-calendars/{institution_department}/classes-config', [AcademicCalendarController::class, 'update'])->name('academic-calendars.classes-config.store');
    Route::post('academic-calendars/{institution_department}/classes-config/{academic_calendar}', [AcademicCalendarController::class, 'storePerClassSizeConfig'])->name('academic-calendars.classes-config.per-class-size.store');
});
