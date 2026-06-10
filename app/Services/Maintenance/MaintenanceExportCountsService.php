<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

use App\Queries\Applications\ApplicationExportQuery;
use App\Queries\Enrolments\StudentEnrollmentExportQuery;
use App\Queries\Maintenance\FaultyStudentIdNumbersQuery;

class MaintenanceExportCountsService
{
    public function __construct(
        protected StudentEnrollmentExportQuery $studentEnrollmentExportQuery,
        protected ApplicationExportQuery $applicationExportQuery,
        protected FaultyStudentIdNumbersQuery $faultyStudentIdNumbersQuery,
    ) {}

    /**
     * @return array{studentEnrolments: int, applications: int, faultyStudentIds: int}
     */
    public function resolve(?string $intakeYear = null): array
    {
        return [
            'studentEnrolments' => $this->studentEnrollmentExportQuery->count($intakeYear),
            'applications' => $this->applicationExportQuery->count($intakeYear),
            'faultyStudentIds' => $this->faultyStudentIdNumbersQuery->count(),
        ];
    }
}
