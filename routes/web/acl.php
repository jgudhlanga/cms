<?php

use App\Http\Controllers\Acl\ModuleController;
use App\Http\Controllers\Acl\PermissionController;
use App\Http\Controllers\Acl\RoleController;
use App\Http\Controllers\Acl\RoleGroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->middleware('auth')->group(function () {
    Route::prefix('acl')->group(function () {
        // ==================================== MODULES ======================================================
        Route::put('modules/{module}/restore', [ModuleController::class, 'restore'])->name('modules.restore');
        Route::put('modules/{module}/settings', [ModuleController::class, 'updateSettings'])->name('modules.settings');
        Route::put('modules/{module}/status', [ModuleController::class, 'updateStatus'])->name('modules.update-status');
        Route::delete('modules/{module}/force-delete', [ModuleController::class, 'forceDelete'])->name('modules.force-delete');
        Route::resource('modules', ModuleController::class)->names('modules');
        // ==================================== PERMISSIONS ==================================================
        Route::put('permissions/{permission}/restore', [PermissionController::class, 'restore'])->name('permissions.restore');
        Route::delete('permissions/{permission}/force-delete', [PermissionController::class, 'forceDelete'])->name('permissions.force-delete');
        Route::resource('permissions', PermissionController::class)->names('permissions');
        // ==================================== ROLES ==================================================
        Route::put('roles/{role}/restore', [RoleController::class, 'restore'])->name('roles.restore');
        Route::delete('roles/{role}/force-delete', [RoleController::class, 'forceDelete'])->name('roles.force-delete');
        Route::put('roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->name('roles.sync-permissions');
        Route::resource('roles', RoleController::class)->names('roles');
        Route::put('role-groups/{role_group}/restore', [RoleGroupController::class, 'restore'])->name('role-groups.restore');
        Route::delete('role-groups/{role_group}/force-delete', [RoleGroupController::class, 'forceDelete'])->name('role-groups.force-delete');
        Route::resource('role-groups', RoleGroupController::class)->names('role-groups');
    });
});
