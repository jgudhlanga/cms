<?php

use App\Http\Controllers\Api\V1\Preferences\UserPreferenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/preferences')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserPreferenceController::class, 'index'])->name('v1.preferences.index');
    Route::post('/', [UserPreferenceController::class, 'store'])->name('v1.preferences.store');
    Route::put('/{preference}', [UserPreferenceController::class, 'update'])->name('v1.preferences.update');
});
