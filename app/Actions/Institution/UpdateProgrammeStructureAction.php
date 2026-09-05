<?php

declare(strict_types=1);

namespace App\Actions\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Institution\DepartmentLevelCourse;
use App\Support\Institution\ProgrammeDurationCalculator;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Support\Facades\DB;

class UpdateProgrammeStructureAction
{
    public function __construct(
        protected SyncProgrammeSemestersForOfferingAction $syncProgrammeSemesters,
    ) {}

    /**
     * @param  array{
     *     duration_years: float|int|string,
     *     taught_semester_count: int,
     *     includes_industrial_attachment: bool,
     *     attachment_semester_count: int
     * }  $data
     */
    public function execute(DepartmentLevelCourse $departmentLevelCourse, array $data): DepartmentLevelCourse
    {
        return DB::transaction(function () use ($departmentLevelCourse, $data): DepartmentLevelCourse {
            $includesAttachment = (bool) $data['includes_industrial_attachment'];
            $taughtCount = (int) $data['taught_semester_count'];
            $attachmentCount = $includesAttachment ? max(1, (int) $data['attachment_semester_count']) : 0;
            $durationYears = $includesAttachment
                ? ProgrammeDurationCalculator::years(
                    $taughtCount,
                    $attachmentCount,
                    $this->periodsPerYear($departmentLevelCourse),
                    true,
                )
                : round((float) $data['duration_years'], 1);

            $departmentLevelCourse->update([
                'duration_years' => $durationYears,
                'taught_semester_count' => $taughtCount,
                'includes_industrial_attachment' => $includesAttachment,
                'attachment_semester_count' => $attachmentCount,
            ]);

            $this->syncProgrammeSemesters->execute($departmentLevelCourse->fresh() ?? $departmentLevelCourse);

            return $departmentLevelCourse->fresh(['programmeSemesters', 'departmentLevel.level']) ?? $departmentLevelCourse;
        });
    }

    private function periodsPerYear(DepartmentLevelCourse $departmentLevelCourse): int
    {
        $departmentLevelCourse->loadMissing(['departmentLevel.level']);
        $calendarType = $departmentLevelCourse->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
                ?? AcademicCalendarTypeEnum::SEMESTER;
        }

        return ProgrammeSemesterNameFormatter::periodsPerYear($calendarType);
    }
}
