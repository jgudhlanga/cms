<?php

use App\Http\Controllers\Api\V1\AcademicCalendars\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/institution')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('academic-calendars', AcademicCalendarController::class)->names('v1.academic-calendars');
});
