<?php


use App\Http\Controllers\Api\V1\Roles\RoleController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1/acl')->group(function () {
    # ==================================== ADDRESS TYPES =================================================
    Route::apiResource('roles', RoleController::class)->names('v1.roles');
});
