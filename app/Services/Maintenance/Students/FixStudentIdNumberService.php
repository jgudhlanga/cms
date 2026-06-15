<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Exceptions\Maintenance\StudentIdNumberConflictException;
use App\Models\Students\Student;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class FixStudentIdNumberService
{
    public function __construct(
        private readonly EnrollmentLookupService $enrollmentLookup,
    ) {}

    public function fix(Student $student, string $idNumber): Student
    {
        if ($student->id_number === null || ZimbabweanIdNumber::isValid($student->id_number)) {
            throw ValidationException::withMessages([
                'id_number' => [__('trans.maintenance_faulty_data_student_not_faulty')],
            ]);
        }

        $normalized = EnrollmentLookupService::normalizeNationalId($idNumber);
        $conflict = $this->enrollmentLookup->findStudentByNationalIdUnscoped($normalized, $student->id);

        if ($conflict !== null) {
            throw new StudentIdNumberConflictException($conflict->id, $normalized);
        }

        try {
            $student->update([
                'id_number' => $normalized,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            $conflict = $this->enrollmentLookup->findStudentByNationalIdUnscoped($normalized, $student->id);

            if ($conflict !== null) {
                throw new StudentIdNumberConflictException($conflict->id, $normalized);
            }

            throw $exception;
        }

        return $student->fresh(['user']);
    }
}
