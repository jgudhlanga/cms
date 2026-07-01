<?php

declare(strict_types=1);

namespace App\Enums\Enrolments;

enum BulkFinaliseEnrolmentAuditEventEnum: string
{
    case RunStarted = 'run_started';
    case StudentFinalised = 'student_finalised';
    case StudentSkipped = 'student_skipped';
    case RunCompleted = 'run_completed';
    case RunFailed = 'run_failed';
}
