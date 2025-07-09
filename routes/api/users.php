<?php

use App\Http\Controllers\Api\V1\Users\UserController;

Route::prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class)->names('v1.users');
});
