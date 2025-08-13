<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Students\StudentProgram;
use App\Http\Requests\Workflows\UploadProofOfPaymentRequest;
use App\Helpers\WorkflowHelper;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApplicationWorkflowController extends Controller
{
    public function uploadProofOfPayment(StudentProgram $studentProgram, UploadProofOfPaymentRequest $request) {
        DB::beginTransaction();
        try {
            $studentProgram->addMedia($request->proof_of_payment)->toMediaCollection('students');
            $file = $studentProgram->getFirstMedia('students');
            $curretStep = $studentProgram->departmentWorkflowStep;
            $step = WorkflowHelper::getDepartmentApplicationStepByPosition($curretStep->position + 1);
            $studentProgram->update(['application_fee_proof_of_payment_id' => $file->id, 'department_application_step_id' => $step->id]);
            DB::commit();
            return back()->with('success', 'Proof of payment uploaded successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'An error occurred while submitting your proof of payment. Please try again.',
            ]);
        }
    }
}
