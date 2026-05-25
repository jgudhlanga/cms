<?php

use App\Http\Controllers\Api\V1\Authentication\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthenticationController::class, 'login'])->name('v1.auth.login');
    Route::post('register', [AuthenticationController::class, 'register'])->name('v1.auth.register');
    Route::post('forgot-password', [AuthenticationController::class, 'forgotPassword'])->name('v1.auth.forgot-password');
    Route::post('logout', [AuthenticationController::class, 'logout'])->middleware('auth:sanctum')->name('v1.auth.logout');
});
