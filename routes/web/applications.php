<?php

use App\Http\Controllers\Applications\ApplicationFormController;
use Illuminate\Support\Facades\Route;


Route::get('/application', [ApplicationFormController::class, 'index'])->name('application.index');
Route::post('/application', [ApplicationFormController::class, 'store'])->name('application.store');

