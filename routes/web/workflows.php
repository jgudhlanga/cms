<?php

use App\Http\Controllers\Workflows\ApplicationWorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('workflows')->middleware('auth')->group(function () {
    # ==================================== APPLICATIONS ================================================================
    Route::post('students/{student_program}/upload-proof-of-payment', [ApplicationWorkflowController::class, 'uploadProofOfPayment'])->name('students.upload-proof-of-payment');
    Route::post('students/{student_program}/approve-application/{department_application_step}', [ApplicationWorkflowController::class, 'approveApplication'])->name('students.approve-application');
    Route::post('students/{institution_department}', [ApplicationWorkflowController::class, 'bulkApproveApplication'])->name('students.bulk-approve-applications');
    Route::post('students/{student_program}/mark-application-fee-payment', [ApplicationWorkflowController::class, 'markApplicationFeePayment'])->name('students.mark-application-fee-payment');
    Route::post('students/{student_program}/mark-tuition-fee-payment', [ApplicationWorkflowController::class, 'markTuitionFeePayment'])->name('students.mark-tuition-fee-payment');
});
