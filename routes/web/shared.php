<?php

use App\Http\Controllers\Shared\AddressController;
use App\Http\Controllers\Shared\BankDetailController;
use App\Http\Controllers\Shared\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
	# ==================================== SCHEMES ============================================
	Route::prefix('shared')->group(function () {
		# =================================== BANK DETAILS ======================================== #
		Route::prefix('bank-details')->group(function () {
			Route::put('{bank_detail}/restore', [BankDetailController::class, 'restore'])->name('bank-details.restore');
			Route::put('{bank_detail}/update', [BankDetailController::class, 'update'])->name('bank-details.update');
			Route::delete('{bank_detail}/force-delete', [BankDetailController::class, 'forceDelete'])->name('bank-details.force-delete');
			Route::delete('{bank_detail}/archive', [BankDetailController::class, 'destroy'])->name('bank-details.destroy');
		});
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
