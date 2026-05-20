<?php

use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::put('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('users/{user}/force-delete', [UserController::class, 'forceDelete'])->name('users.force-delete');
    Route::post('users/store-staff-user', [UserController::class, 'storeStaffUser'])->name('users.store-staff-user');
    Route::put('users/{user}/update-staff-user', [UserController::class, 'updateStaffUser'])->name('users.update-staff-user');
    Route::put('users/{user}/update-student-user', [UserController::class, 'updateStudentUser'])->name('users.update-student-user');
    Route::put('users/{user}/update-user-credentials', [UserController::class, 'updateUserCredentials'])->name('users.update-user-credentials');
    Route::resource('users', UserController::class)->names('users');
});

