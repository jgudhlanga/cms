<?php

use App\Http\Controllers\Workflows\ApplicationWorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('workflows')->middleware('auth')->group(function () {
    # ==================================== APPLICATIONS ================================================================
    Route::post('students/{student_program}', [ApplicationWorkflowController::class, 'uploadProofOfPayment'])->name('students.upload-proof-of-payment');
});
