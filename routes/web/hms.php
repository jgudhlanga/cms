<?php

use App\Http\Controllers\HMS\HostelController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::put('hostels/{hostel}/restore', [HostelController::class, 'restore'])->name('hostels.restore');
    Route::delete('hostels/{hostel}/force-delete', [HostelController::class, 'forceDelete'])->name('hostels.force-delete');

    Route::resource('hostels', HostelController::class)->only(['index', 'show', 'store', 'update', 'destroy'])->names('hostels');
});
