<?php

use App\Http\Controllers\AcademicCalendars\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::prefix('institution')->middleware('auth')->group(function () {
    #===================================== ACADEMIC CALENDARS ==========================================================
    Route::get('academic-calendars', [AcademicCalendarController::class, 'index'])->name('academic-calendars.index');
    Route::post('academic-calendars', [AcademicCalendarController::class, 'store'])->name('academic-calendars.store');
});
