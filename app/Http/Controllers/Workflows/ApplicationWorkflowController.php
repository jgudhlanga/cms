<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflows\BulkUpdatePaymentStatusRequest;
use Illuminate\Http\RedirectResponse;
use App\Models\Students\StudentProgram;
use App\Http\Requests\Workflows\UploadProofOfPaymentRequest;
use App\Http\Requests\Workflows\BulkApplicationApproveRequest;
use App\Helpers\WorkflowHelper;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\InstitutionDepartment;

class ApplicationWorkflowController extends Controller
{
    /**
     * @throws Throwable
     */
    public function uploadProofOfPayment(StudentProgram $studentProgram, UploadProofOfPaymentRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $type = $request->type;
            $mediaCollection = 'application-fee';
            $field = 'application_fee_proof_of_payment_id';
            if ($type === 'tuition_fee') {
                $mediaCollection = 'tuition-fee';
                $field = 'tuition_fee_proof_of_payment_id';
            }
            $studentProgram->addMedia($request->proof_of_payment)->toMediaCollection($mediaCollection);
            $file = $studentProgram->getMedia($mediaCollection)->last();
            $currentStep = $studentProgram->departmentWorkflowStep;
            $step = WorkflowHelper::getDepartmentApplicationStepByPosition($studentProgram->institution_department_id, $currentStep->position + 1);
            $studentProgram->update([$field => $file->id, 'department_application_step_id' => $step->id]);
            DB::commit();
            return back()->with('success', 'Proof of payment uploaded successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'An error occurred while submitting your proof of payment. Please try again.',
            ]);
        }
    }

    /**
     * @throws Throwable
     */
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

    /**
     * @throws Throwable
     */
    public function bulkApproveApplication(InstitutionDepartment $institutionDepartment, BulkApplicationApproveRequest $request): RedirectResponse
    {
        $intakePeriodId = $request->filled('intake_period_id') ? $request->intake_period_id : null;
        $modeOfStudyId = $request->filled('mode_of_study_id') ? $request->mode_of_study_id : null;
        $departmentLevelId = $request->filled('department_level_id') ? $request->department_level_id : null;
        $currentStepId = $request->filled('current_step_id') ? $request->current_step_id : null;
        $newStepId = $request->filled('new_step_id') ? $request->new_step_id : null;

        DB::beginTransaction();
        try {
            $institutionDepartment->enrolments()
                ->when($departmentLevelId, fn($q) => $q->where('department_level_id', $departmentLevelId))
                ->when($currentStepId, fn($q) => $q->where('department_application_step_id', $currentStepId))
                ->when($intakePeriodId, fn($q) => $q->where('intake_period_id', $intakePeriodId))
                ->when($modeOfStudyId, fn($q) => $q->where('mode_of_study_id', $modeOfStudyId))
                ->update(['department_application_step_id' => $newStepId]);
            DB::commit();
            return back()->with('success', 'Bulk application approval done successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e); // optional: log for debugging
            return back()->withErrors([
                'error' => 'An error occurred while bulk approving applications. Please try again.',
            ]);
        }
    }

    /**
     * @throws Throwable
     */
    public function bulkUpdatePaymentStatuses(InstitutionDepartment $institutionDepartment, BulkUpdatePaymentStatusRequest $request): RedirectResponse
    {
        $intakePeriodId = $request->filled('intake_period_id') ? $request->intake_period_id : null;
        $modeOfStudyId = $request->filled('mode_of_study_id') ? $request->mode_of_study_id : null;
        $departmentLevelId = $request->filled('department_level_id') ? $request->department_level_id : null;
        $currentStepId = $request->filled('current_step_id') ? $request->current_step_id : null;
        $fieldToUpdate = $request->filled('field_to_update') ? $request->field_to_update : 'registration_fee_confirmed';
        $fieldValue = $request->filled('field_value') ? $request->field_value : false;

        DB::beginTransaction();
        try {
            $institutionDepartment->enrolments()
                ->when($departmentLevelId, fn($q) => $q->where('department_level_id', $departmentLevelId))
                ->when($currentStepId, fn($q) => $q->where('department_application_step_id', $currentStepId))
                ->when($intakePeriodId, fn($q) => $q->where('intake_period_id', $intakePeriodId))
                ->when($modeOfStudyId, fn($q) => $q->where('mode_of_study_id', $modeOfStudyId))
                ->update([$fieldToUpdate => $fieldValue]);
            DB::commit();
            return back()->with('success', 'Bulk payment status done successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e); // optional: log for debugging
            return back()->withErrors([
                'error' => 'An error occurred while bulk updating payment status. Please try again.',
            ]);
        }
    }

    /**
     * @throws Throwable
     */
    public function confirmRegistrationFeePayment(StudentProgram $studentProgram): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $studentProgram->update(['registration_fee_confirmed' => !$studentProgram->registration_fee_confirmed]);
            DB::commit();
            return back()->with('success', 'Registration fee successfully confirmed');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'An error occurred while confirming registration fee payment. Please try again.',
            ]);
        }
    }

    /**
     * @throws Throwable
     */
    public function confirmTuitionFeePayment(StudentProgram $studentProgram): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $studentProgram->update(['tuition_fee_confirmed' => !$studentProgram->tuition_fee_confirmed]);
            DB::commit();
            return back()->with('success', 'Tuition fee successfully confirmed');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'An error occurred while confirming tuition fee payment. Please try again.',
            ]);
        }
    }

}
