<?php

use App\Http\Controllers\Api\V1\HMS\HostelController;
use App\Http\Controllers\Api\V1\HMS\HostelRoomAllocationController;
use App\Http\Controllers\Api\V1\HMS\HostelRoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/hms')->middleware('auth:sanctum')->group(function () {
    Route::get('hostels', [HostelController::class, 'index'])->name('v1.hms.hostels');
    Route::get('hostel-rooms/stats', [HostelRoomController::class, 'stats'])->name('v1.hms.hostels.rooms.stats');
    Route::get('hostel-rooms', [HostelRoomController::class, 'index'])->name('v1.hms.hostels.rooms');
    Route::get('hostel-allocations', [HostelRoomAllocationController::class, 'index'])->name('v1.hms.hostel-allocations');
});
