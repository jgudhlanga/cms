<?php

use App\Http\Controllers\Lecturer\ClassesController;
use App\Http\Controllers\Lecturer\DashboardController;
use App\Http\Controllers\Lecturer\ModulesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'redirect.student'])
    ->prefix('lecturer')
    ->name('lecturer.')
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('classes', [ClassesController::class, 'index'])->name('classes.index');
        Route::get('modules', [ModulesController::class, 'index'])->name('modules.index');
    });
