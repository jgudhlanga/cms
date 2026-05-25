<?php

use App\Http\Controllers\Api\V1\Users\UserController;

Route::prefix('v1')->group(function () {
    Route::get('users/{user}/permissions', [UserController::class, 'getUserPermissions'])->name('v1.users.permissions');
    Route::apiResource('users', UserController::class)->names('v1.users');
});
