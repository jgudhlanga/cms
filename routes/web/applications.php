<?php

use App\Http\Controllers\Applications\ApplicationFormController;
use Illuminate\Support\Facades\Route;


Route::get('/application', [ApplicationFormController::class, 'create'])->name('applications.create');
Route::post('/application', [ApplicationFormController::class, 'store'])->name('applications.store');
Route::get('/application/{user}', [ApplicationFormController::class, 'confirmation'])->name('applications.confirmation');;

