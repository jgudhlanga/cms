<?php

use App\Http\Controllers\AcademicCalendars\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::prefix('institution')->middleware('auth')->group(function () {
    #===================================== ACADEMIC CALENDARS ==========================================================
    Route::get('academic-calendars', [AcademicCalendarController::class, 'index'])->name('academic-calendars.index');
    Route::post('academic-calendars', [AcademicCalendarController::class, 'store'])->name('academic-calendars.store');
    Route::put('academic-calendars/{academic_calendar}', [AcademicCalendarController::class, 'update'])->name('academic-calendars.update');
    Route::get('academic-calendars/{institution_department}/classes-config/{academic_calendar}', [AcademicCalendarController::class, 'classConfig'])->name('academic-calendars.classes-config');
    Route::post('academic-calendars/{institution_department}/classes-config', [AcademicCalendarController::class, 'update'])->name('academic-calendars.classes-config.store');
});
