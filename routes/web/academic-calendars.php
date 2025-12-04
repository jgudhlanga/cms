<?php

use App\Http\Controllers\AcademicCalendars\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('academic-calendars')->group(function () {
    Route::put('{academic_calendar}/restore', [AcademicCalendarController::class, 'restore'])->name('academic-calendars.restore');
    Route::delete('{academic_calendar}/force-delete', [AcademicCalendarController::class, 'forceDelete'])->name('academic-calendars.force-delete');
});
Route::middleware('auth')->resource('academic-calendars', AcademicCalendarController::class)->names('academic-calendars');
