<?php

use App\Http\Controllers\Api\V1\HMS\HostelRoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/hms')->middleware('auth:sanctum')->group(function () {
    Route::get('hostel-rooms/stats', [HostelRoomController::class, 'stats'])->name('v1.hms.hostels.rooms.stats');
});
