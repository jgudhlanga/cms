<?php

use App\Http\Controllers\Api\V1\HMS\HostelController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/hms')->middleware('auth:sanctum')->group(function () {
    Route::get('hostels', [HostelController::class, 'index'])->name('v1.hms.hostels');
});
