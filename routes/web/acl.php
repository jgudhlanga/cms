<?php

use App\Http\Controllers\Acl\AclController;
use App\Http\Controllers\Acl\ModuleController;
use App\Http\Controllers\Acl\PermissionController;
use App\Http\Controllers\Acl\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->middleware('auth')->group(function () {
	Route::prefix('acl')->group(function () {
		Route::get('/', AclController::class)->name('acl.index');
		# ==================================== MODULES ======================================================
		Route::put('modules/{module}/restore', [ModuleController::class, 'restore'])->name('modules.restore');
		Route::delete('modules/{module}/force-delete', [ModuleController::class, 'forceDelete'])->name('modules.force-delete');
		Route::resource('modules', ModuleController::class)->names('modules');
		# ==================================== PERMISSIONS ==================================================
		Route::put('permissions/{permission}/restore', [PermissionController::class, 'restore'])->name('permissions.restore');
		Route::delete('permissions/{permission}/force-delete', [PermissionController::class, 'forceDelete'])->name('permissions.force-delete');
		Route::resource('permissions', PermissionController::class)->names('permissions');
		# ==================================== ROLES ==================================================
		Route::put('roles/{role}/restore', [RoleController::class, 'restore'])->name('roles.restore');
		Route::delete('roles/{role}/force-delete', [RoleController::class, 'forceDelete'])->name('roles.force-delete');
		Route::resource('roles', RoleController::class)->names('roles');
	});
});
