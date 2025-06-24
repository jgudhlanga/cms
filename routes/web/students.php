<?php

use App\Http\Controllers\Students\SponsorController;
use Illuminate\Support\Facades\Route;


Route::prefix('students')->middleware('auth')->group(function () {
    # ==================================== SPONSORS ================================================================
    Route::post('sponsors', [SponsorController::class, 'store'])->name('sponsors.store');
    Route::put('sponsors/{sponsor}/restore', [SponsorController::class, 'restore'])->name('sponsors.restore');
    Route::put('sponsors/{sponsor}/update', [SponsorController::class, 'update'])->name('sponsors.update');
    Route::delete('sponsors/{sponsor}/delete', [SponsorController::class, 'destroy'])->name('sponsors.destroy');
    Route::delete('sponsors/{sponsor}/force-delete', [SponsorController::class, 'forceDelete'])->name('sponsors.force-delete');
});

