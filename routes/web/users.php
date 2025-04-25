<?php

use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
	Route::resource('users', UserController::class)->names('users');
});
