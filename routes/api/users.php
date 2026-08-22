<?php

use App\Http\Controllers\Api\V1\Users\UserController;

Route::prefix('v1')->group(function () {
    Route::get('me/activities', [UserController::class, 'getMyActivities'])->name('v1.me.activities');
    Route::get('users/activity-lookup', [UserController::class, 'activityLookup'])->name('v1.users.activity-lookup');
    Route::get('users/{user}/permissions', [UserController::class, 'getUserPermissions'])->name('v1.users.permissions');
    Route::get('users/{user}/activities', [UserController::class, 'getUserActivities'])->name('v1.users.activities');
    Route::get('users/{user}/caused-activities', [UserController::class, 'getUserCausedActivities'])->name('v1.users.caused-activities');
    Route::put('users/{user}/preferences', [UserController::class, 'updateUserPreferences'])->name('v1.users.preferences.update');
    Route::apiResource('users', UserController::class)->names('v1.users');
});
