<?php

use App\Http\Controllers\HMS\HostelController;
use App\Http\Controllers\HMS\HostelRoomController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::put('hostels/{hostel}/restore', [HostelController::class, 'restore'])->name('hostels.restore');
    Route::delete('hostels/{hostel}/force-delete', [HostelController::class, 'forceDelete'])->name('hostels.force-delete');

    Route::resource('hostels', HostelController::class)->only(['index', 'show', 'store', 'update', 'destroy'])->names('hostels');

    Route::put('hostel-rooms/{hostelRoom}/restore', [HostelRoomController::class, 'restore'])->name('hostel-rooms.restore');
    Route::delete('hostel-rooms/{hostelRoom}/force-delete', [HostelRoomController::class, 'forceDelete'])->name('hostel-rooms.force-delete');

    Route::resource('hostel-rooms', HostelRoomController::class)->only(['store', 'update', 'destroy'])->names('hostel-rooms');
});
