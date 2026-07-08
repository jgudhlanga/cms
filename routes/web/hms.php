<?php

use App\Http\Controllers\HMS\HostelApplicationController;
use App\Http\Controllers\HMS\HostelAmenityController;
use App\Http\Controllers\HMS\HostelController;
use App\Http\Controllers\HMS\HostelRoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('hms')->middleware('auth')->group(function () {
    Route::put('hostels/{hostel}/restore', [HostelController::class, 'restore'])->name('hostels.restore');
    Route::delete('hostels/{hostel}/force-delete', [HostelController::class, 'forceDelete'])->name('hostels.force-delete');

    Route::get('hostels/applications/{hostel_application}', [HostelApplicationController::class, 'showApplication'])
        ->name('hostels.applications.show');

    Route::resource('hostels', HostelController::class)->only(['index', 'show', 'store', 'update', 'destroy'])->names('hostels');

    Route::put('hostel-amenities/{hostelAmenity}/restore', [HostelAmenityController::class, 'restore'])->name('hostel-amenities.restore');
    Route::delete('hostel-amenities/{hostelAmenity}/force-delete', [HostelAmenityController::class, 'forceDelete'])->name('hostel-amenities.force-delete');
    Route::resource('hostel-amenities', HostelAmenityController::class)->only(['store', 'update', 'destroy'])->names('hostel-amenities');

    Route::put('hostel-rooms/{hostelRoom}/restore', [HostelRoomController::class, 'restore'])->name('hostel-rooms.restore');
    Route::delete('hostel-rooms/{hostelRoom}/force-delete', [HostelRoomController::class, 'forceDelete'])->name('hostel-rooms.force-delete');
    Route::put('hostel-rooms/{hostelRoom}/sections/{hostelRoomSection}/amenities', [HostelRoomController::class, 'syncSectionAmenities'])
        ->name('hostel-rooms.sections.amenities.sync');

    Route::resource('hostel-rooms', HostelRoomController::class)->only(['show', 'store', 'update', 'destroy'])->names('hostel-rooms');
});
