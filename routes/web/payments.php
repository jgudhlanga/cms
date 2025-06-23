<?php

use App\Http\Controllers\Shared\PaymentDayController;
use App\Http\Controllers\Shared\PaymentFrequencyController;
use App\Http\Controllers\Shared\PaymentMethodController;
use App\Http\Controllers\Shared\PaymentSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->middleware('auth')->group(function () {
	Route::prefix('payments')->group(function () {
		Route::get('/', PaymentSettingsController::class)->name('payments-index');
		# ==================================== DAYS ======================================================
		Route::put('payment-days/{payment_day}/restore', [PaymentDayController::class, 'restore'])->name('payment-days.restore');
		Route::delete('payment-days/{payment_day}/force-delete', [PaymentDayController::class, 'forceDelete'])->name('payment-days.force-delete');
		Route::resource('payment-days', PaymentDayController::class)->names('payment-days');
		# ==================================== FREQUENCIES ======================================================
		Route::put('payment-frequencies/{payment_frequency}/restore', [PaymentFrequencyController::class, 'restore'])->name('payment-frequencies.restore');
		Route::delete('payment-frequencies/{payment_frequency}/force-delete', [PaymentFrequencyController::class, 'forceDelete'])->name('payment-frequencies.force-delete');
		Route::resource('payment-frequencies', PaymentFrequencyController::class)->names('payment-frequencies');
		# ==================================== METHODS ======================================================
		Route::put('payment-methods/{payment_method}/restore', [PaymentMethodController::class, 'restore'])->name('payment-methods.restore');
		Route::delete('payment-methods/{payment_method}/force-delete', [PaymentMethodController::class, 'forceDelete'])->name('payment-methods.force-delete');
		Route::resource('payment-methods', PaymentMethodController::class)->names('payment-methods');
	});
});
