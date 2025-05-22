<?php

use App\Http\Controllers\Applications\ApplicationController;
use Illuminate\Support\Facades\Route;


Route::get('/application', [ApplicationController::class, 'create'])->name('applications.create');
Route::post('/application', [ApplicationController::class, 'store'])->name('applications.store');
Route::get('/application/{user}', [ApplicationController::class, 'confirmation'])->name('applications.confirmation');
Route::get('/application/{user}/list', [ApplicationController::class, 'index'])->name('applications.index');
Route::get('/application/{user}/edit', [ApplicationController::class, 'edit'])->name('applications.edit');

