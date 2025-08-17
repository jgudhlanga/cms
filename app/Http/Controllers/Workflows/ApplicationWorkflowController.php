<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
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
    public function uploadProofOfPayment(StudentProgram $studentProgram, UploadProofOfPaymentRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $studentProgram->addMedia($request->proof_of_payment)->toMediaCollection('students');
            $file = $studentProgram->getFirstMedia('students');
            $currentStep = $studentProgram->departmentWorkflowStep;
            $step = WorkflowHelper::getDepartmentApplicationStepByPosition($studentProgram->institution_department_id,$currentStep->position + 1);
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

    public function approveApplication(StudentProgram $studentProgram, DepartmentApplicationStep $departmentApplicationStep): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $studentProgram->update(['department_application_step_id' => $departmentApplicationStep->id]);
            DB::commit();
            return back()->with('success', 'Application successfully moved to new step');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'An error occurred while moving application to new workflow step. Please try again.',
            ]);
        }
    }

    public function bulkApproveApplication(InstitutionDepartment $institutionDepartment,  BulkApplicationApproveRequest $request): RedirectResponse
    {
        $intakePeriodId = $request->filled('intake_period_id') ? $request->intake_period_id : null;
        $departmentLevelId = $request->filled('department_level_id') ? $request->department_level_id : null;
        $currentStepId = $request->filled('current_step_id') ? $request->current_step_id : null;
        $newStepId = $request->filled('new_step_id') ? $request->new_step_id : null;

        DB::beginTransaction();
        try {
             $institutionDepartment->enrolments()
                ->when($departmentLevelId, fn($q) => $q->where('department_level_id', $departmentLevelId))
                ->when($currentStepId, fn($q) => $q->where('department_application_step_id', $currentStepId))
                ->when($intakePeriodId, fn($q) => $q->where('intake_period_id', $intakePeriodId))
                ->update(['department_application_step_id' => $newStepId]);
            DB::commit();
            return back()->with('success', 'Bulk application done successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e); // optional: log for debugging
            return back()->withErrors([
                'error' => 'An error occurred while bulk approving applications. Please try again.',
            ]);
        }
    }

    public function markApplicationFeePayment(StudentProgram $studentProgram): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $studentProgram->update(['application_fee_paid' => !$studentProgram->application_fee_paid]);
            DB::commit();
            return back()->with('success', 'Application fee payment successfully updated');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'An error occurred while updating application fee payment. Please try again.',
            ]);
        }
    }

    public function markTuitionFeePayment(StudentProgram $studentProgram): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $studentProgram->update(['tuition_fee_paid' => !$studentProgram->tuition_fee_paid]);
            DB::commit();
            return back()->with('success', 'Tuition fee payment successfully updated');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'An error occurred while updating tuition fee payment. Please try again.',
            ]);
        }
    }

}
