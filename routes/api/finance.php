<?php

use App\Http\Controllers\Api\V1\Finance\FinanceReceiptController;
use App\Http\Controllers\Api\V1\Finance\FinanceTransactionQueryController;

Route::prefix('v1/financials')->middleware('auth:sanctum')->group(function () {
    Route::get('students/{student}/receipts', [FinanceReceiptController::class, 'getStudentReceipts'])
        ->name('v1.financials.student.receipts');
    Route::get('students/{student}/ledger', [FinanceReceiptController::class, 'getStudentLedger'])
        ->name('v1.financials.student.ledger');
    Route::get('students/{student}/transaction-queries', [FinanceTransactionQueryController::class, 'indexForStudent'])
        ->name('v1.financials.student.transaction-queries.index');
    Route::post('students/{student}/transaction-queries', [FinanceTransactionQueryController::class, 'storeForStudent'])
        ->name('v1.financials.student.transaction-queries.store');

    Route::get('reconciliation/transaction-queries', [FinanceTransactionQueryController::class, 'indexForReconciliation'])
        ->name('v1.financials.reconciliation.transaction-queries.index');
    Route::patch('reconciliation/transaction-queries/{transactionQuery}/under-review', [FinanceTransactionQueryController::class, 'markUnderReview'])
        ->name('v1.financials.reconciliation.transaction-queries.under-review');
    Route::patch('reconciliation/transaction-queries/{transactionQuery}/needs-info', [FinanceTransactionQueryController::class, 'markNeedsInfo'])
        ->name('v1.financials.reconciliation.transaction-queries.needs-info');
    Route::get('reconciliation/transaction-queries/{transactionQuery}/preview-match', [FinanceTransactionQueryController::class, 'previewMatch'])
        ->name('v1.financials.reconciliation.transaction-queries.preview-match');
    Route::patch('reconciliation/transaction-queries/{transactionQuery}/reconcile', [FinanceTransactionQueryController::class, 'reconcile'])
        ->name('v1.financials.reconciliation.transaction-queries.reconcile');
    Route::patch('reconciliation/transaction-queries/{transactionQuery}/decline', [FinanceTransactionQueryController::class, 'decline'])
        ->name('v1.financials.reconciliation.transaction-queries.decline');
});
