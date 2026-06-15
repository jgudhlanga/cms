<?php

use App\Http\Controllers\Api\V1\Users\UserController;

Route::prefix('v1')->group(function () {
    Route::get('users/{user}/permissions', [UserController::class, 'getUserPermissions'])->name('v1.users.permissions');
    Route::get('users/{user}/activities', [UserController::class, 'getUserActivities'])->name('v1.users.activities');
    Route::put('users/{user}/preferences', [UserController::class, 'updateUserPreferences'])->name('v1.users.preferences.update');
    Route::apiResource('users', UserController::class)->names('v1.users');
});
