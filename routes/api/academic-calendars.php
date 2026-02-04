<?php

use App\Http\Controllers\Api\V1\AcademicCalendars\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/institution')->middleware('auth:sanctum')->group(function () {
    Route::get('academic-calendar-options', [AcademicCalendarController::class, 'getOptions'])->name('v1.academic-calendar.options');
    Route::get('academic-calendars', [AcademicCalendarController::class, 'index'])->name('v1.academic-calendar.index');
});
