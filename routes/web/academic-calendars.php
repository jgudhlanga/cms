<?php

use App\Http\Controllers\AcademicCalendars\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::prefix('institution')->middleware('auth')->group(function () {
    #===================================== ACADEMIC CALENDARS ==========================================================
    Route::put('academic-calendars/{academic_calendar}/restore', [AcademicCalendarController::class, 'restore'])->name('academic-calendars.restore');
    Route::delete('academic-calendars/{academic_calendar}/force-delete', [AcademicCalendarController::class, 'forceDelete'])->name('academic-calendars.force-delete');
    Route::resource('academic-calendars', AcademicCalendarController::class)->names('academic-calendars');
});
