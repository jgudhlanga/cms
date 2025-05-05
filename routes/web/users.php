<?php

use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
	Route::put('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
	Route::delete('users/{user}/force-delete', [UserController::class, 'forceDelete'])->name('users.force-delete');
	Route::resource('users', UserController::class)->names('users');
});

