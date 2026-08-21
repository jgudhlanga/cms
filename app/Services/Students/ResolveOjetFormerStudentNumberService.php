<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Helpers\Helper;
use App\Models\Students\Student;
use App\Services\Enrollment\EnrollmentLookupService;

class ResolveOjetFormerStudentNumberService
{
    public const IDENTITY_ZIMBABWEAN = 'zimbabwean';

    public const IDENTITY_INTERNATIONAL = 'international';

    public function __construct(
        protected EnrollmentLookupService $enrollmentLookup,
    ) {}

    public function resolve(string $identityType, string $identityValue): OjetFormerStudentResolution
    {
        $normalizedIdentity = $this->normalizeIdentity($identityType, $identityValue);
        $student = $this->findStudent($identityType, $normalizedIdentity);

        if ($student instanceof Student) {
            $existingNumber = $this->nonEmptyStudentNumber($student->student_number);

            if ($existingNumber !== null) {
                return OjetFormerStudentResolution::fromStudent($student, $existingNumber);
            }

            $legacyNumber = $this->lookupLegacyStudentNumber($normalizedIdentity);

            if ($legacyNumber === null) {
                return OjetFormerStudentResolution::unresolved();
            }

            if ($this->studentNumberBelongsToAnotherStudent($legacyNumber, $student->id)) {
                return OjetFormerStudentResolution::conflict();
            }

            return OjetFormerStudentResolution::fromStudent($student, $legacyNumber);
        }

        $legacyNumber = $this->lookupLegacyStudentNumber($normalizedIdentity);

        if ($legacyNumber === null) {
            return OjetFormerStudentResolution::unresolved();
        }

        if ($this->studentNumberBelongsToAnotherStudent($legacyNumber, null)) {
            return OjetFormerStudentResolution::conflict();
        }

        return OjetFormerStudentResolution::fromLegacyNumber($legacyNumber);
    }

    public function normalizeIdentity(string $identityType, string $identityValue): string
    {
        if ($identityType === self::IDENTITY_INTERNATIONAL) {
            return EnrollmentLookupService::normalizePassportNumber($identityValue);
        }

        return EnrollmentLookupService::normalizeNationalId($identityValue);
    }

    /**
     * Safe wrapper: never treat Helper's "CSV file not found" sentinel as a student number.
     */
    public function lookupLegacyStudentNumber(string $identity): ?string
    {
        $value = Helper::lookupLegacyStudentNumber($identity);

        if (! is_string($value) || $value === '' || $value === 'CSV file not found') {
            return null;
        }

        $normalized = EnrollmentLookupService::normalizeStudentNumber($value);

        return $normalized !== '' ? $normalized : null;
    }

    private function findStudent(string $identityType, string $normalizedIdentity): ?Student
    {
        if ($identityType === self::IDENTITY_INTERNATIONAL) {
            return $this->enrollmentLookup->findStudentByPassport($normalizedIdentity);
        }

        return $this->enrollmentLookup->findStudentByNationalId($normalizedIdentity);
    }

    private function nonEmptyStudentNumber(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = EnrollmentLookupService::normalizeStudentNumber($value);

        return $normalized !== '' ? $normalized : null;
    }

    private function studentNumberBelongsToAnotherStudent(string $studentNumber, ?int $excludingStudentId): bool
    {
        $query = Student::query()
            ->where('student_number', EnrollmentLookupService::normalizeStudentNumber($studentNumber));

        if ($excludingStudentId !== null) {
            $query->where('id', '!=', $excludingStudentId);
        }

        return $query->exists();
    }
}
