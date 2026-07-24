<?php

use App\Http\Controllers\Api\V1\Rbac\RoleController;
use App\Http\Controllers\Api\V1\Rbac\RoleGroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/rbac')->group(function () {
    # ==================================== ROLES  =================================================
    Route::apiResource('role-groups', RoleGroupController::class)->names('v1.role-groups');
    Route::apiResource('roles', RoleController::class)->names('v1.roles');
});
