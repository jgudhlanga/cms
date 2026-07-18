<?php

use App\Http\Controllers\Teaching\ClassesController;
use App\Http\Controllers\Teaching\ModulesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'redirect.student'])
    ->prefix('teaching')
    ->name('teaching.')
    ->group(function () {
        Route::get('classes', [ClassesController::class, 'index'])->name('classes.index');
        Route::get('modules', [ModulesController::class, 'index'])->name('modules.index');
    });

Route::middleware(['auth', 'verified', 'redirect.student'])->group(function () {
    Route::redirect('lecturer/dashboard', '/dashboard');
    Route::redirect('lecturer/classes', '/teaching/classes');
    Route::redirect('lecturer/modules', '/teaching/modules');
});
