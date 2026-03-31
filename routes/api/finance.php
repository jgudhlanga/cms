<?php

use App\Http\Controllers\Api\V1\Finance\FinanceReceiptController;

Route::prefix('v1/financials')->middleware('auth:sanctum')->group(function () {
    Route::get('students/{student}/receipts', [FinanceReceiptController::class, 'getStudentReceipts'])
        ->name('v1.financials.student.receipts');
});
