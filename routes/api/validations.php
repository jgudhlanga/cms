<?php

use App\Http\Controllers\Api\V1\Validations\ValidationController;
use Illuminate\Support\Facades\Route;

// Uniqueness checks reveal whether an email/ID exists, so they are throttled.
Route::prefix('v1/validations')->middleware('throttle:120,1')->group(function () {
    Route::get('check', [ValidationController::class, 'check'])->name('v1.check');
});
