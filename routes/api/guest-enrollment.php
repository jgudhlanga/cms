<?php

use App\Http\Controllers\Api\V1\Guest\GuestEnrollmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/guest/enrollment')
    ->middleware('throttle:5,1')
    ->group(function () {
        Route::post('check-national-id', [GuestEnrollmentController::class, 'checkNationalId'])
            ->name('v1.guest.enrollment.check-national-id');
        Route::post('check-passport', [GuestEnrollmentController::class, 'checkPassport'])
            ->name('v1.guest.enrollment.check-passport');
        Route::post('lookup', [GuestEnrollmentController::class, 'lookup'])
            ->name('v1.guest.enrollment.lookup');
    });
