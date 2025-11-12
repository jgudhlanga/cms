<?php

use App\Http\Controllers\Documents\DocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('documents')->group(function () {
    # ==================================== OFFER LETTER ======================================================
    Route::get('offer-letter/{student_program}', [DocumentController::class, 'previewOfferLetter'])->name('documents.offer-letter');
});
