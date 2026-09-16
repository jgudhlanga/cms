<?php

use App\Http\Controllers\Setup\SetupGapController;
use App\Http\Controllers\Users\NotificationController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('setup-gaps', [SetupGapController::class, 'index'])->name('setup-gaps.index');
    Route::post('setup-gaps/refresh', [SetupGapController::class, 'refresh'])
        ->middleware('throttle:20,1')
        ->name('setup-gaps.refresh');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

Route::middleware(['auth', 'verified', 'redirect.student'])->group(function () {
    Route::get('users/audit-trail', [UserController::class, 'auditTrail'])->name('users.audit-trail');
});

Route::middleware('auth')->group(function () {
    Route::put('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('users/{user}/force-delete', [UserController::class, 'forceDelete'])->name('users.force-delete');
    Route::post('users/store-staff-user', [UserController::class, 'storeStaffUser'])->name('users.store-staff-user');
    Route::put('users/{user}/update-staff-user', [UserController::class, 'updateStaffUser'])->name('users.update-staff-user');
    Route::put('users/{user}/update-user-credentials', [UserController::class, 'updateUserCredentials'])
        ->middleware('impersonate.protect')
        ->name('users.update-user-credentials');
    Route::put('users/{user}/update-user-names', [UserController::class, 'updateUserNames'])
        ->middleware('impersonate.protect')
        ->name('users.update-user-names');
    Route::resource('users', UserController::class)->names('users');
});
