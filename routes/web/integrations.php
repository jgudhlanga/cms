<?php

use App\Http\Controllers\Integrations\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('integrations')->middleware('auth')->group(function () {
    # ==================================== PAYMENTS ======================================================
    Route::prefix('payments')->group(function () {
        Route::post('initiate', [PaymentController::class, 'initiatePayment'])->name('integrations.payments.initiate');
        Route::get('feedback', [PaymentController::class, 'feedback'])->name('integrations.payments.feedback');
        Route::get('cancel', [PaymentController::class, 'cancelled'])->name('integrations.payments.cancel');
        Route::get('failure', [PaymentController::class, 'failed'])->name('integrations.payments.failure');
        Route::post('result', [PaymentController::class, 'result'])->name('integrations.payments.result');
    });
});
