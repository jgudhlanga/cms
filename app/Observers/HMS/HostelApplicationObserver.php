<?php

namespace App\Observers\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Models\HMS\HmsSetting;
use App\Models\HMS\HostelApplication;
use App\Models\Students\Student;
use App\Services\HMS\HostelApplicationApprovalService;
use App\Services\HMS\HostelApplicationEligibilityService;
use App\Services\HMS\HostelApplicationPendingService;
use App\Services\HMS\HostelApplicationReviewService;
use App\Services\HMS\HostelApplicationSemesterService;
use App\Services\HMS\HostelRoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class HostelApplicationObserver
{
    public function __construct(
        protected HostelApplicationEligibilityService $eligibilityService,
        protected HostelApplicationSemesterService $semesterService,
        protected HostelRoomAvailabilityService $roomAvailabilityService,
        protected HostelApplicationPendingService $pendingService,
        protected HostelApplicationApprovalService $approvalService,
        protected HostelApplicationReviewService $reviewService,
    ) {}

    public function creating(HostelApplication $application): void
    {
        if ($application->status === null) {
            $application->status = HostelApplicationStatusEnum::PENDING;
        }

        $this->applyTypeDefaults($application);

        if ($application->type === HostelApplicationTypeEnum::STUDENT) {
            $this->applyStudentSemesterDates($application);
            $this->guardStudentHostelCapacity($application);
        }

        $this->validateApplication($application);
        $this->guardDuplicatePending($application);
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
        }

        if ($application->isDirty('status')
            && $application->status === HostelApplicationStatusEnum::APPROVED
            && $application->getOriginal('status') !== HostelApplicationStatusEnum::APPROVED->value) {
            $hostelRoomId = (int) data_get(request()->input('data'), 'attributes.hostelRoomId', 0);

            if ($hostelRoomId < 1) {
                throw ValidationException::withMessages([
                    'hostelRoomId' => [__('hms.hostel_room_required_for_approval')],
                ]);
            }

            $this->approvalService->approve($application, $hostelRoomId);
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

        if ($application->status === HostelApplicationStatusEnum::AWAITING_PAYMENT
            && $previousValue === HostelApplicationStatusEnum::PENDING->value) {
            $this->reviewService->dispatchAwaitingPaymentEmail($application);
        }

        if ($application->status === HostelApplicationStatusEnum::DECLINED
            && $previousValue === HostelApplicationStatusEnum::PENDING->value) {
            $this->reviewService->dispatchDeclinedEmail($application);
        }
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

            $application->eligibility_results = $this->eligibilityService->evaluate(
                $student,
                $enrolment,
            );
        }
    }

    private function applyStudentSemesterDates(HostelApplication $application): void
    {
        $student = Student::query()
            ->with(['latestEnrolment.studentProgram.intakePeriod'])
            ->find($application->student_id);

        if ($student === null) {
            return;
        }

        $asOf = Carbon::now((string) config('app.timezone'));
        $semesterDates = $this->semesterService->datesForApplication($student, $asOf);

        if (! $semesterDates['success']) {
            throw ValidationException::withMessages([
                'check_in' => [$this->messageForSemesterBlocker($semesterDates['blocker'])],
            ]);
        }

        $timezone = (string) config('app.timezone');
        $application->check_in = Carbon::parse($semesterDates['checkIn'], $timezone)->startOfDay();
        $application->check_out = Carbon::parse($semesterDates['checkOut'], $timezone)->startOfDay();
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

    private function messageForSemesterBlocker(?string $blocker): string
    {
        return match ($blocker) {
            HostelApplicationSemesterService::BLOCKER_CALENDAR_YEAR_MISSING => __('hms.calendar_year_missing'),
            default => __('hms.no_running_semester'),
        };
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
}
