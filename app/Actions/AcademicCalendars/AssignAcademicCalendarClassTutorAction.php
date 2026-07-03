<?php

declare(strict_types=1);

namespace App\Actions\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Services\AcademicCalendars\ClassStaffingService;

class AssignAcademicCalendarClassTutorAction
{
    public function __construct(
        private readonly ClassStaffingService $classStaffingService,
    ) {}

    public function execute(AcademicCalendarClass $academicCalendarClass, ?int $staffId, int $tenantId): void
    {
        $this->classStaffingService->assignTutor($academicCalendarClass, $staffId, $tenantId);
    }
}
