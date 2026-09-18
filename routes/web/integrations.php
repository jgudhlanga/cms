<?php

use App\Http\Controllers\Integrations\PaymentController;
use App\Http\Controllers\Integrations\PaymentDebugController;
use App\Http\Controllers\Integrations\PaymentGatewaySettingsController;
use Illuminate\Support\Facades\Route;

Route::post('integrations/payments/result', [PaymentController::class, 'result'])
    ->middleware('throttle:60,1')
    ->name('integrations.payments.result');

Route::prefix('integrations')->middleware('auth')->group(function () {
    Route::prefix('payment-gateway')->group(function () {
        Route::get('/', [PaymentGatewaySettingsController::class, 'index'])->name('integrations.payment-gateway.index');
        Route::put('/', [PaymentGatewaySettingsController::class, 'update'])->name('integrations.payment-gateway.update');
        Route::post('unlock', [PaymentGatewaySettingsController::class, 'unlock'])
            ->middleware('throttle:payment-gateway-unlock')
            ->name('integrations.payment-gateway.unlock');
        Route::post('lock', [PaymentGatewaySettingsController::class, 'lock'])->name('integrations.payment-gateway.lock');
        Route::post('touch', [PaymentGatewaySettingsController::class, 'touch'])->name('integrations.payment-gateway.touch');
    });

    Route::prefix('payments-debug')->group(function () {
        Route::get('/', [PaymentDebugController::class, 'index'])
            ->middleware('can:viewPaymentsDebug')
            ->name('integrations.payments-debug.index');
        Route::get('ledgers', [PaymentDebugController::class, 'search'])
            ->middleware('can:viewPaymentsDebug')
            ->name('integrations.payments-debug.search');
        Route::post('{order_reference}/check', [PaymentDebugController::class, 'check'])
            ->middleware('can:viewPaymentsDebug')
            ->name('integrations.payments-debug.check');
        Route::post('update-status', [PaymentDebugController::class, 'update'])
            ->middleware('can:updatePaymentsDebug')
            ->name('integrations.payments-debug.update');
    });

    // ==================================== PAYMENTS ======================================================
    Route::prefix('payments')->group(function () {
        Route::post('initiate', [PaymentController::class, 'initiatePayment'])->name('integrations.payments.initiate');
        Route::get('feedback', [PaymentController::class, 'feedback'])->name('integrations.payments.feedback');
        Route::get('cancel', [PaymentController::class, 'cancelled'])->name('integrations.payments.cancel');
        Route::get('failure', [PaymentController::class, 'failed'])->name('integrations.payments.failure');
        Route::post('check-payment-status-for-current-user', [PaymentController::class, 'checkPaymentStatusForCurrenUser'])->name('check-payment-status-for-current-user');

        Route::get('payment-status', fn () => redirect()->route('integrations.payments-debug.index'))
            ->name('integrations.payments.check-status-create');
    });
});
