<?php

use App\Http\Controllers\Workflows\ApplicationWorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('workflows')->middleware('auth')->group(function () {
    # ==================================== APPLICATIONS ================================================================
    Route::post('students/{student_application}/upload-proof-of-payment', [ApplicationWorkflowController::class, 'uploadProofOfPayment'])->name('students.upload-proof-of-payment');
    Route::post('students/{student_application}/approve-application/{department_application_step}', [ApplicationWorkflowController::class, 'approveApplication'])->name('students.approve-application');
    Route::post('students/{institution_department}/bulk-approve-applications', [ApplicationWorkflowController::class, 'bulkApproveApplication'])->name('students.bulk-approve-applications');
    Route::post('students/{institution_department}/bulk-update-payment-statuses', [ApplicationWorkflowController::class, 'bulkUpdatePaymentStatuses'])->name('students.bulk-update-payment-statuses');
    Route::post('students/{student_application}/confirm-registration-fee-payment', [ApplicationWorkflowController::class, 'confirmRegistrationFeePayment'])->name('students.confirm-registration-fee-payment');
    Route::post('students/{student_application}/confirm-tuition-fee-payment', [ApplicationWorkflowController::class, 'confirmTuitionFeePayment'])->name('students.confirm-tuition-fee-payment');
});
