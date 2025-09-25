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
        Route::post('payment-status/{order_reference}', [PaymentController::class, 'checkStatus'])->name('integrations.payments.check-status');
        Route::post('check-payment-status-for-current-user', [PaymentController::class, 'checkPaymentStatusForCurrenUser'])->name('check-payment-status-for-current-user');
        Route::post('update-status', [PaymentController::class, 'updateLedgerRecords'])->name('integrations.payments.update-status');
        Route::get('payment-status', [PaymentController::class, 'createCheckStatus'])->name('integrations.payments.check-status-create');
    });
});
