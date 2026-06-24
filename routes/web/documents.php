<?php

use App\Http\Controllers\Documents\DocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('documents')->group(function () {
    # ==================================== OFFER LETTER ======================================================
    Route::get('offer-letter/{student_application}', [DocumentController::class, 'previewOfferLetter'])->name('documents.offer-letter');

    Route::middleware('auth')->group(function () {
        Route::get('transaction-statement/{student}', [DocumentController::class, 'exportTransactionStatement'])
            ->name('documents.transaction-statement');
    });
});
