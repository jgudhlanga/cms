<?php

namespace App\Services\HMS;

use App\Models\HMS\HostelRoomAllocation;

class HostelStudentAllocationService
{
    public const BLOCKER_STUDENT_ALREADY_ALLOCATED = 'student_already_allocated';

    public function studentHasOpenAllocation(int $studentId): bool
    {
        return HostelRoomAllocation::query()
            ->notCheckedOut()
            ->where('student_id', $studentId)
            ->exists();
    }
}
