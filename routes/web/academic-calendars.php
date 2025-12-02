<?php

use App\Http\Controllers\AcademicCalendars\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->resource('academic-calendars', AcademicCalendarController::class)->names('academic-calendars');
