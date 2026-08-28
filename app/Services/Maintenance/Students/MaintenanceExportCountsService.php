<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Queries\Applications\ApplicationExportQuery;
use App\Queries\Enrolments\StudentEnrollmentExportQuery;
use App\Queries\Maintenance\FaultyApplicationsQuery;
use App\Queries\Maintenance\FaultyStudentIdNumbersQuery;

class MaintenanceExportCountsService
{
    public function __construct(
        protected StudentEnrollmentExportQuery $studentEnrollmentExportQuery,
        protected ApplicationExportQuery $applicationExportQuery,
        protected FaultyStudentIdNumbersQuery $faultyStudentIdNumbersQuery,
        protected FaultyApplicationsQuery $faultyApplicationsQuery,
    ) {}

    /**
     * @param  array<string, mixed>|string|null  $filters
     * @return array{studentEnrolments: int, applications: int, faultyStudentIds: int, faultyApplications: int}
     */
    public function resolve(array|string|null $filters = null): array
    {
        return [
            'studentEnrolments' => $this->studentEnrollmentExportQuery->count($filters),
            'applications' => $this->applicationExportQuery->count($filters),
            'faultyStudentIds' => $this->faultyStudentIdNumbersQuery->count(),
            'faultyApplications' => $this->faultyApplicationsQuery->count(),
        ];
    }
}
