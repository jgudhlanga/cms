<?php

namespace App\Observers\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\HMS\HostelEligibilityContextEnum;
use App\Models\HMS\HmsSetting;
use App\Models\HMS\HostelApplication;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Services\HMS\HostelApplicationApprovalService;
use App\Services\HMS\HostelApplicationEligibilityService;
use App\Services\HMS\HostelApplicationPaymentService;
use App\Services\HMS\HostelApplicationPendingService;
use App\Services\HMS\HostelApplicationReviewService;
use App\Services\HMS\HostelApplicationSemesterService;
use App\Services\HMS\HostelApplicationWindowService;
use App\Services\HMS\HostelRoomAvailabilityService;
use App\Services\HMS\HostelStudentAllocationService;
use App\Support\HMS\HostelApplicationPaymentVerification;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class HostelApplicationObserver
{
    public function __construct(
        protected HostelApplicationEligibilityService $eligibilityService,
        protected HostelApplicationSemesterService $semesterService,
        protected HostelApplicationWindowService $windowService,
        protected HostelRoomAvailabilityService $roomAvailabilityService,
        protected HostelApplicationPendingService $pendingService,
        protected HostelStudentAllocationService $allocationService,
        protected HostelApplicationApprovalService $approvalService,
        protected HostelApplicationPaymentService $paymentService,
        protected HostelApplicationReviewService $reviewService,
    ) {}

    public function creating(HostelApplication $application): void
    {
        if ($application->status === null) {
            $application->status = HostelApplicationStatusEnum::PENDING;
        }

        $this->applyTypeDefaults($application);

        if ($application->type === HostelApplicationTypeEnum::STUDENT) {
            $this->guardApplicationWindow($application);
            $this->applyStudentSemesterDates($application);
            $this->guardStudentHostelCapacity($application);
        }

        $this->validateApplication($application);
        $this->guardDuplicatePending($application);
        $this->guardOpenAllocation($application);
    }

    public function updating(HostelApplication $application): void
    {
        if ($application->isDirty('status')
            && $application->status === HostelApplicationStatusEnum::DECLINED
            && blank($application->decline_reason)) {
            throw ValidationException::withMessages([
                'decline_reason' => [__('hms.decline_reason_required')],
            ]);
        }

        if ($application->isDirty(['type', 'student_id', 'student_enrolment_id'])) {
            $this->applyTypeDefaults($application);
        }

        if ($application->isDirty('status')
            && $application->status === HostelApplicationStatusEnum::AWAITING_PAYMENT) {
            $this->reviewService->guardRequestPayment($application);

            $previousStatus = $application->getOriginal('status');
            $previousValue = $previousStatus instanceof HostelApplicationStatusEnum
                ? $previousStatus->value
                : (string) $previousStatus;

            if ($previousValue === HostelApplicationStatusEnum::PENDING->value) {
                $this->refreshEligibilityForAwaitingPayment($application);
            }

            $settings = HmsSetting::resolveForTenant($application->tenant_id);
            $application->payment_due_at = now()->addDays($settings->days_to_pay);
        }

        if ($application->isDirty('status')
            && in_array($application->status, [
                HostelApplicationStatusEnum::PAID,
                HostelApplicationStatusEnum::APPROVED,
                HostelApplicationStatusEnum::DECLINED,
            ], true)) {
            $application->payment_due_at = null;
        }

        if ($application->isDirty('status')
            && $application->status === HostelApplicationStatusEnum::APPROVED
            && $application->getOriginal('status') !== HostelApplicationStatusEnum::APPROVED->value) {
            $paymentVerificationInput = data_get(request()->input('data'), 'attributes.paymentVerification');
            $settings = HmsSetting::resolveForTenant($application->tenant_id);

            if (! HostelApplicationPaymentVerification::isCompleteFromApi(
                is_array($paymentVerificationInput) ? $paymentVerificationInput : null,
                $settings,
            )) {
                throw ValidationException::withMessages([
                    'paymentVerification' => [__('hms.payment_verification_incomplete')],
                ]);
            }

            $this->mergePaymentVerificationFromRequest($application);

            $hostelRoomId = (int) data_get(request()->input('data'), 'attributes.hostelRoomId', 0);

            if (! $settings->auto_allocate_rooms && $hostelRoomId < 1) {
                throw ValidationException::withMessages([
                    'hostelRoomId' => [__('hms.hostel_room_required_for_approval')],
                ]);
            }

            $this->approvalService->approve($application, $hostelRoomId > 0 ? $hostelRoomId : null);
        }
    }

    public function updated(HostelApplication $application): void
    {
        if (! $application->wasChanged('status')) {
            return;
        }

        $previousStatus = $application->getOriginal('status');
        $previousValue = $previousStatus instanceof HostelApplicationStatusEnum
            ? $previousStatus->value
            : (string) $previousStatus;

        if (in_array($application->status, [
            HostelApplicationStatusEnum::AWAITING_PAYMENT,
            HostelApplicationStatusEnum::PARTIALLY_PAID,
        ], true)) {
            $this->syncStudentPaymentStatus($application);
            $application->refresh();
        }

        if ($application->status === HostelApplicationStatusEnum::AWAITING_PAYMENT
            && $previousValue === HostelApplicationStatusEnum::PENDING->value) {
            $this->reviewService->dispatchAwaitingPaymentEmail($application);
        }

        if ($application->status === HostelApplicationStatusEnum::DECLINED
            && in_array($previousValue, [
                HostelApplicationStatusEnum::PENDING->value,
                HostelApplicationStatusEnum::AWAITING_PAYMENT->value,
                HostelApplicationStatusEnum::PARTIALLY_PAID->value,
                HostelApplicationStatusEnum::PAID->value,
            ], true)) {
            $this->reviewService->dispatchDeclinedEmail($application);
        }

        if ($application->status === HostelApplicationStatusEnum::APPROVED
            && $previousValue !== HostelApplicationStatusEnum::APPROVED->value) {
            $this->reviewService->dispatchRoomAllocationEmail($application);
        }
    }

    private function syncStudentPaymentStatus(HostelApplication $application): void
    {
        if ($application->type !== HostelApplicationTypeEnum::STUDENT || blank($application->student_id)) {
            return;
        }

        $student = Student::query()->find($application->student_id);

        if ($student === null) {
            return;
        }

        $this->paymentService->syncOpenApplicationForStudent($student);
    }

    private function applyTypeDefaults(HostelApplication $application): void
    {
        if ($application->type === HostelApplicationTypeEnum::STUDENT) {
            $student = Student::query()
                ->with(['user', 'gender', 'latestEnrolment'])
                ->find($application->student_id);

            if ($student === null) {
                return;
            }

            $application->gender_id ??= $student->gender_id;
            $application->student_enrolment_id ??= $student->latestEnrolment?->id;
            $application->phone_number ??= $student->user?->phone_number;
            $application->email_address ??= $student->user?->email;
            $application->name ??= $student->user?->full_name;

            $enrolment = $application->student_enrolment_id
                ? $student->enrolments()->find($application->student_enrolment_id)
                : $student->latestEnrolment;

            $this->applyEligibilitySnapshot(
                $application,
                $student,
                $enrolment,
                HostelEligibilityContextEnum::APPLICATION,
            );
        }
    }

    private function refreshEligibilityForAwaitingPayment(HostelApplication $application): void
    {
        if ($application->type !== HostelApplicationTypeEnum::STUDENT || blank($application->student_id)) {
            return;
        }

        $student = Student::query()
            ->with(['latestEnrolment.modeOfStudy', 'latestEnrolment.studentApplication'])
            ->find($application->student_id);

        if ($student === null) {
            return;
        }

        $enrolment = $application->student_enrolment_id
            ? $student->enrolments()->with(['modeOfStudy', 'studentApplication'])->find($application->student_enrolment_id)
            : $student->latestEnrolment;

        $this->applyEligibilitySnapshot(
            $application,
            $student,
            $enrolment,
            HostelEligibilityContextEnum::AWAITING_PAYMENT,
        );
    }

    private function applyEligibilitySnapshot(
        HostelApplication $application,
        Student $student,
        ?StudentEnrolment $enrolment,
        HostelEligibilityContextEnum $context,
    ): void {
        $rules = $this->eligibilityService->evaluate($student, $enrolment, context: $context);
        $application->eligibility_results = $rules;
        $application->address_outside_campus_priority = $this->eligibilityService->addressOutsideCampusPassed($rules);
    }

    private function guardApplicationWindow(HostelApplication $application): void
    {
        $window = $this->windowService->windowStatus($application->tenant_id);

        if (! $window['success']) {
            throw ValidationException::withMessages([
                'check_in' => [__('hms.applications_closed')],
            ]);
        }
    }

    private function applyStudentSemesterDates(HostelApplication $application): void
    {
        $student = Student::query()
            ->with(['latestEnrolment.studentApplication.intakePeriod'])
            ->find($application->student_id);

        if ($student === null) {
            return;
        }

        $asOf = Carbon::now((string) config('app.timezone'));
        $semesterDates = $this->semesterService->datesForApplication($student, $asOf);
        $applicationDates = $this->windowService->configuredApplicationDates($application->tenant_id);

        $checkIn = $semesterDates['success']
            ? $semesterDates['checkIn']
            : $applicationDates['checkIn'];
        $checkOut = $semesterDates['success']
            ? $semesterDates['checkOut']
            : $applicationDates['checkOut'];

        if ($checkIn === null || $checkOut === null) {
            return;
        }

        $timezone = (string) config('app.timezone');
        $application->check_in = Carbon::parse($checkIn, $timezone)->startOfDay();
        $application->check_out = Carbon::parse($checkOut, $timezone)->startOfDay();
    }

    private function guardStudentHostelCapacity(HostelApplication $application): void
    {
        if (blank($application->gender_id)) {
            throw ValidationException::withMessages([
                'gender_id' => [__('hms.unknown_gender_for_hostel')],
            ]);
        }

        $summary = $this->roomAvailabilityService->summaryForGender((int) $application->gender_id);

        if ($summary['blocker'] === HostelRoomAvailabilityService::BLOCKER_UNKNOWN_GENDER) {
            throw ValidationException::withMessages([
                'gender_id' => [__('hms.unknown_gender_for_hostel')],
            ]);
        }

        if ($summary['blocker'] === HostelRoomAvailabilityService::BLOCKER_NO_HOSTEL_CAPACITY) {
            throw ValidationException::withMessages([
                'check_in' => [__('hms.no_hostel_capacity')],
            ]);
        }
    }

    private function validateApplication(HostelApplication $application): void
    {
        if ($application->check_in && $application->check_out
            && $application->check_out->lte($application->check_in)) {
            throw ValidationException::withMessages([
                'check_out' => [__('hms.check_out_after_check_in')],
            ]);
        }

        if ($application->type === HostelApplicationTypeEnum::STUDENT && blank($application->student_id)) {
            throw ValidationException::withMessages([
                'student_id' => [__('hms.student_required')],
            ]);
        }

        if ($application->type === HostelApplicationTypeEnum::GUEST) {
            if (! HmsSetting::resolveForTenant($application->tenant_id)->allow_guests) {
                throw ValidationException::withMessages([
                    'applicationType' => [__('hms.guest_applications_disabled')],
                ]);
            }

            if (blank($application->name)) {
                throw ValidationException::withMessages([
                    'name' => [__('hms.guest_name_required')],
                ]);
            }

            if (blank($application->gender_id)) {
                throw ValidationException::withMessages([
                    'gender_id' => [__('hms.gender_required')],
                ]);
            }
        }

        if (blank($application->next_of_kin_name) || blank($application->next_of_kin_contact)) {
            throw ValidationException::withMessages([
                'next_of_kin_name' => [__('hms.next_of_kin_required')],
            ]);
        }
    }

    private function guardDuplicatePending(HostelApplication $application): void
    {
        if ($application->type !== HostelApplicationTypeEnum::STUDENT || blank($application->student_id)) {
            return;
        }

        if ($this->pendingService->studentHasPendingApplication(
            (int) $application->student_id,
            $application->exists ? (int) $application->id : null,
        )) {
            throw ValidationException::withMessages([
                'student_id' => [__('hms.student_pending_application_exists')],
            ]);
        }
    }

    private function mergePaymentVerificationFromRequest(HostelApplication $application): void
    {
        $input = data_get(request()->input('data'), 'attributes.paymentVerification');

        if (! is_array($input)) {
            return;
        }

        $application->payment_verification = array_merge(
            HostelApplicationPaymentVerification::normalize($application->payment_verification),
            HostelApplicationPaymentVerification::fromApi($input),
        );
    }

    private function guardOpenAllocation(HostelApplication $application): void
    {
        if ($application->type !== HostelApplicationTypeEnum::STUDENT || blank($application->student_id)) {
            return;
        }

        if ($this->allocationService->studentHasOpenAllocation((int) $application->student_id)) {
            throw ValidationException::withMessages([
                'student_id' => [__('hms.student_already_allocated')],
            ]);
        }
    }
}
