<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Students\StudentProgram;
use App\Http\Requests\Workflows\{UploadProofOfPaymentRequest,BulkApplicationApproveRequest};
use App\Helpers\WorkflowHelper;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\InstitutionDepartment;

class ApplicationWorkflowController extends Controller
{
    public function uploadProofOfPayment(StudentProgram $studentProgram, UploadProofOfPaymentRequest $request) {
        DB::beginTransaction();
        try {
            $studentProgram->addMedia($request->proof_of_payment)->toMediaCollection('students');
            $file = $studentProgram->getFirstMedia('students');
            $curretStep = $studentProgram->departmentWorkflowStep;
            $step = WorkflowHelper::getDepartmentApplicationStepByPosition($studentProgram->institution_department_id,$curretStep->position + 1);
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

    public function approveApplication(StudentProgram $studentProgram, DepartmentApplicationStep $departmentApplicationStep) {
        DB::beginTransaction();
        try {
            $studentProgram->update(['department_application_step_id' => $departmentApplicationStep->id]);
            DB::commit();
            return back()->with('success', 'Application succefully moved to new step');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'An error occurred while moving appplication to new workflow step. Please try again.',
            ]);
        }
    }

    public function bulkApproveApplication(InstitutionDepartment $institutionDepartment, BulkApplicationApproveRequest $request) {
        DB::beginTransaction();
        try {

            DB::commit();
            return back()->with('success', 'Bulk application done succefully');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'An error occurred while bulk approving applications. Please try again.',
            ]);
        }
    }
}
