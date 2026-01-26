<?php

use App\Http\Controllers\Api\V1\Validations\ValidationController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1/validations')->group(function () {
    Route::get('check', [ValidationController::class, 'check'])->name('v1.check');
});
