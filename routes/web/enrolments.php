<?php

use App\Http\Controllers\Enrolments\EnrolmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('enrolments', EnrolmentController::class)->names('enrolments');
});

