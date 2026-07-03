<?php

declare(strict_types=1);

namespace App\Actions\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Services\AcademicCalendars\ClassStaffingService;

class CopySyllabusModuleLecturersToClassAction
{
    public function __construct(
        private readonly ClassStaffingService $classStaffingService,
    ) {}

    public function execute(
        AcademicCalendarClass $academicCalendarClass,
        ClassConfig $allocationConfig,
        int $academicYearOptionId,
        int $tenantId,
    ): void {
        $semesterConfig = $this->classStaffingService->resolveSemesterClassConfig(
            $allocationConfig,
            $academicYearOptionId,
        );

        abort_unless($semesterConfig instanceof ClassConfig, 422, __('academic_calendar.semester_config_missing'));

        $modules = $this->classStaffingService->resolveSemesterModules($semesterConfig);

        $this->classStaffingService->copyTemplateLecturersToClass(
            $academicCalendarClass,
            $modules,
            $tenantId,
        );
    }
}
