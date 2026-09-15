<?php

use App\Http\Controllers\Api\V1\Rbac\RoleController;
use App\Http\Controllers\Api\V1\Rbac\RoleGroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/rbac')->middleware('auth:sanctum')->group(function () {
    // ==================================== ROLES  =================================================
    Route::apiResource('role-groups', RoleGroupController::class)->only(['index'])->names('v1.role-groups');
    Route::apiResource('roles', RoleController::class)->only(['index'])->names('v1.roles');
});
