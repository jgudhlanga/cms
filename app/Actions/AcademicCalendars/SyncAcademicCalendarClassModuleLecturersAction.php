<?php

declare(strict_types=1);

namespace App\Actions\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Services\AcademicCalendars\ClassStaffingService;

class SyncAcademicCalendarClassModuleLecturersAction
{
    public function __construct(
        private readonly ClassStaffingService $classStaffingService,
    ) {}

    /**
     * @param  list<int>  $staffIds
     */
    public function execute(
        AcademicCalendarClass $academicCalendarClass,
        ClassConfig $allocationConfig,
        int $academicYearOptionId,
        CourseSyllabusModule $module,
        array $staffIds,
        int $tenantId,
    ): void {
        $semesterConfig = $this->classStaffingService->resolveSemesterClassConfig(
            $allocationConfig,
            $academicYearOptionId,
        );

        abort_unless(
            $semesterConfig instanceof ClassConfig
            && $this->classStaffingService->moduleBelongsToSemesterConfig($module, $semesterConfig),
            422,
            __('academic_calendar.module_not_in_semester'),
        );

        $this->classStaffingService->syncClassModuleLecturers(
            $academicCalendarClass,
            $module,
            $staffIds,
            $tenantId,
        );
    }
}
