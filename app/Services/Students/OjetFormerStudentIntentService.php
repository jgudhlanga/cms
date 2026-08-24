<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Students\Student;
use App\Services\Enrollment\EnrollmentLookupService;

class OjetFormerStudentIntentService
{
    public function __construct(
        protected RegistrationIntentSession $intentSession,
        protected ApplicationTrackSession $trackSession,
        protected ApplicationEligibilityService $eligibility,
        protected ApplicationFeeService $applicationFeeService,
    ) {}

    public function hasPendingProgrammeIntent(): bool
    {
        return $this->intentSession->stepperVariant() === 'ojet'
            && $this->intentSession->hasResolvedOjetFormerStudent()
            && $this->intentSession->hasProgrammeSelection();
    }

    public function hasPromotedOjetApplicationIntent(): bool
    {
        return is_string(session('application.ojet_student_number'))
            && session('application.ojet_student_number') !== ''
            && session('application.mode_of_study_id') !== null;
    }

    public function studentMatchesVerifiedIdentity(Student $student): bool
    {
        $identityType = $this->intentSession->ojetIdentityType()
            ?? session('application.ojet_identity_type');

        if ($identityType === ResolveOjetFormerStudentNumberService::IDENTITY_INTERNATIONAL) {
            $expected = $this->intentSession->ojetPassportNumber()
                ?? session('application.ojet_passport_number');
            $actual = $student->passport_number;

            if (! is_string($expected) || ! is_string($actual) || $actual === '') {
                return false;
            }

            return EnrollmentLookupService::normalizePassportNumber($actual)
                === EnrollmentLookupService::normalizePassportNumber($expected);
        }

        $expected = $this->intentSession->ojetIdNumber()
            ?? session('application.ojet_id_number');
        $actual = $student->id_number;

        if (! is_string($expected) || ! is_string($actual) || $actual === '') {
            return false;
        }

        return EnrollmentLookupService::normalizeNationalId($actual)
            === EnrollmentLookupService::normalizeNationalId($expected);
    }

    public function promotePendingIntent(): void
    {
        if (! $this->hasPendingProgrammeIntent()) {
            return;
        }

        $this->intentSession->promoteToApplicationSession($this->trackSession);
    }

    public function ensureStudentNumber(Student $student): void
    {
        $resolved = $this->intentSession->ojetStudentNumber()
            ?? session('application.ojet_student_number');

        if (! is_string($resolved) || $resolved === '') {
            return;
        }

        $normalized = EnrollmentLookupService::normalizeStudentNumber($resolved);
        $current = is_string($student->student_number)
            ? EnrollmentLookupService::normalizeStudentNumber($student->student_number)
            : '';

        if ($current !== '') {
            return;
        }

        if (Student::query()->where('student_number', $normalized)->where('id', '!=', $student->id)->exists()) {
            return;
        }

        $student->update([
            'student_number' => $normalized,
            'student_number_generated' => true,
        ]);
    }
}
