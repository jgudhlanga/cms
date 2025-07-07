<?php


use App\Http\Controllers\Api\V1\Acl\RoleController;
use App\Http\Controllers\Api\V1\Acl\RoleGroupController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1/acl')->group(function () {
    # ==================================== ROLES  =================================================
    Route::apiResource('role-groups', RoleGroupController::class)->names('v1.role-groups');
    Route::apiResource('roles', RoleController::class)->names('v1.roles');
});
