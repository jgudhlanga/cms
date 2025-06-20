<?php

use App\Http\Controllers\Shared\AddressController;
use App\Http\Controllers\Shared\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
	# ==================================== SCHEMES ============================================
	Route::prefix('shared')->group(function () {
		# =================================== CONTACT DETAILS ======================================== #
		Route::prefix('contacts')->group(function () {
			Route::put('{contact}/restore', [ContactController::class, 'restore'])->name('contacts.restore');
			Route::put('{contact}/update', [ContactController::class, 'update'])->name('contacts.update');
			Route::delete('{contact}/force-delete', [ContactController::class, 'forceDelete'])->name('contacts.force-delete');
			Route::delete('{contact}/archive', [ContactController::class, 'destroy'])->name('contacts.destroy');
		});
		# =================================== ADDRESS DETAILS ======================================== #
		Route::prefix('addresses')->group(function () {
			Route::put('{address}/restore', [AddressController::class, 'restore'])->name('addresses.restore');
			Route::put('{address}/update', [AddressController::class, 'update'])->name('addresses.update');
			Route::delete('{address}/force-delete', [AddressController::class, 'forceDelete'])->name('addresses.force-delete');
			Route::delete('{address}/archive', [AddressController::class, 'destroy'])->name('addresses.destroy');
		});
	});

});
