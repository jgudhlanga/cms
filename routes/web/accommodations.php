<?php

use App\Http\Controllers\AcademicCalendars\AcademicCalendarController;
use App\Http\Controllers\Accommodations\AccommodationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('accommodations')->group(function () {
    Route::put('{accommodation}/restore', [AccommodationController::class, 'restore'])->name('accommodations.restore');
    Route::delete('{accommodation}/force-delete', [AccommodationController::class, 'forceDelete'])->name('accommodations.force-delete');
});
Route::middleware('auth')->resource('accommodations', AccommodationController::class)->names('accommodations');
