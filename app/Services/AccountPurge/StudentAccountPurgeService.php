<?php

declare(strict_types=1);

namespace App\Services\AccountPurge;

use App\Models\Students\Student;
use App\Models\Users\User;
use Illuminate\Validation\ValidationException;

class StudentAccountPurgeService
{
    public function __construct(
        private readonly AccountPurgeArchiveService $archiveService,
    ) {}

    public function isPurgeEligible(Student $student, int $tenantId): bool
    {
        return (int) $student->tenant_id === $tenantId;
    }

    public function purge(Student $student, User $purgedBy, string $reason, int $tenantId): void
    {
        if (! $this->isPurgeEligible($student, $tenantId)) {
            throw ValidationException::withMessages([
                'student' => [__('trans.student_account_purge_not_eligible')],
            ]);
        }

        $this->archiveService->purgeStudentAccount($student, $purgedBy, $reason, $tenantId);
    }
}
