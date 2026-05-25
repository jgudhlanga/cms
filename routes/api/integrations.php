<?php

use App\Http\Controllers\Integrations\PaymentController;

Route::prefix('v1')->group(function () {
    # ==================================== ADDRESS TYPES =================================================
    Route::get('payments/check-level-requires-application-fee-payment/{level}', [PaymentController::class, 'checkLevelRequiresApplicationFeePayment'])
        ->name('v1.payments.check-level-requires-application-fee-payment');
    Route::get('payments/check-user-intake-period-application-fee-payment-status/{level}', [PaymentController::class, 'checkUserIntakePeriodApplicationFeePaymentStatus'])
        ->name('v1.payments.check-user-intake-period-application-fee-payment-status');
});
