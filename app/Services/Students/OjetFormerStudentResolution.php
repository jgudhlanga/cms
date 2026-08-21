<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Students\Student;

readonly class OjetFormerStudentResolution
{
    public function __construct(
        public bool $resolved,
        public ?string $studentNumber = null,
        public ?Student $student = null,
        public bool $hasPortalUser = false,
        public ?string $errorKey = null,
    ) {}

    public static function unresolved(string $errorKey = 'ojet_former_student_not_found'): self
    {
        return new self(resolved: false, errorKey: $errorKey);
    }

    public static function conflict(string $errorKey = 'ojet_former_student_number_conflict'): self
    {
        return new self(resolved: false, errorKey: $errorKey);
    }

    public static function fromStudent(Student $student, string $studentNumber): self
    {
        return new self(
            resolved: true,
            studentNumber: $studentNumber,
            student: $student,
            hasPortalUser: $student->user_id !== null && $student->user !== null,
        );
    }

    public static function fromLegacyNumber(string $studentNumber): self
    {
        return new self(
            resolved: true,
            studentNumber: $studentNumber,
        );
    }

    public function isOnCurrentPortal(): bool
    {
        return $this->student instanceof Student;
    }
}
