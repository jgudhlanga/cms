<?php

declare(strict_types=1);

namespace App\Actions\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\ProgrammeSemesterKindEnum;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeSemester;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SyncProgrammeSemestersForOfferingAction
{
    /**
     * @return Collection<int, ProgrammeSemester>
     */
    public function execute(DepartmentLevelCourse $departmentLevelCourse): Collection
    {
        $departmentLevelCourse->loadMissing(['departmentLevel.level']);

        return DB::transaction(function () use ($departmentLevelCourse): Collection {
            $calendarType = $departmentLevelCourse->departmentLevel?->level?->calendar_type
                ?? AcademicCalendarTypeEnum::SEMESTER;

            if (! $calendarType instanceof AcademicCalendarTypeEnum) {
                $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
                    ?? AcademicCalendarTypeEnum::SEMESTER;
            }

            $periodsPerYear = ProgrammeSemesterNameFormatter::periodsPerYear($calendarType);
            $taughtCount = max(1, (int) $departmentLevelCourse->taught_semester_count);
            $includesAttachment = (bool) $departmentLevelCourse->includes_industrial_attachment;
            $attachmentCount = $includesAttachment
                ? max(1, (int) $departmentLevelCourse->attachment_semester_count)
                : 0;

            $desired = $this->buildDesiredRows(
                $departmentLevelCourse,
                $calendarType,
                $periodsPerYear,
                $taughtCount,
                $attachmentCount,
            );

            $existing = ProgrammeSemester::query()
                ->where('department_level_course_id', $departmentLevelCourse->id)
                ->orderBy('position')
                ->get()
                ->keyBy('position');

            $synced = collect();

            foreach ($desired as $row) {
                $position = (int) $row['position'];
                $current = $existing->get($position);

                if ($current instanceof ProgrammeSemester) {
                    if ($this->canUpdateProgrammeSemester($current)) {
                        $current->update([
                            'name' => $row['name'],
                            'kind' => $row['kind'],
                        ]);
                    }
                    $synced->push($current->fresh() ?? $current);

                    continue;
                }

                $synced->push(ProgrammeSemester::query()->create([
                    'department_level_course_id' => $departmentLevelCourse->id,
                    'position' => $position,
                    'name' => $row['name'],
                    'kind' => $row['kind'],
                ]));
            }

            $desiredPositions = $desired->pluck('position')->map(fn (mixed $p): int => (int) $p)->all();

            ProgrammeSemester::query()
                ->where('department_level_course_id', $departmentLevelCourse->id)
                ->whereNotIn('position', $desiredPositions)
                ->whereDoesntHave('studentSemesters')
                ->delete();

            return $synced->sortBy('position')->values();
        });
    }

    /**
     * @return Collection<int, array{position: int, name: string, kind: ProgrammeSemesterKindEnum}>
     */
    private function buildDesiredRows(
        DepartmentLevelCourse $departmentLevelCourse,
        AcademicCalendarTypeEnum $calendarType,
        int $periodsPerYear,
        int $taughtCount,
        int $attachmentCount,
    ): Collection {
        $rows = collect();
        $position = 1;

        for ($taughtIndex = 1; $taughtIndex <= $taughtCount; $taughtIndex++) {
            $yearNumber = (int) ceil($taughtIndex / $periodsPerYear);
            $periodInYear = (($taughtIndex - 1) % $periodsPerYear) + 1;

            $rows->push([
                'position' => $position++,
                'name' => ProgrammeSemesterNameFormatter::taughtName($calendarType, $yearNumber, $periodInYear),
                'kind' => ProgrammeSemesterKindEnum::TAUGHT,
            ]);
        }

        if ($attachmentCount > 0) {
            $taughtYears = (int) max(1, ceil($taughtCount / $periodsPerYear));
            $attachmentStartYear = $taughtYears + 1;

            for ($attachmentIndex = 1; $attachmentIndex <= $attachmentCount; $attachmentIndex++) {
                $yearNumber = (int) ceil($attachmentIndex / $periodsPerYear) + $attachmentStartYear - 1;
                $periodInYear = (($attachmentIndex - 1) % $periodsPerYear) + 1;

                $rows->push([
                    'position' => $position++,
                    'name' => ProgrammeSemesterNameFormatter::attachmentName($yearNumber, $periodInYear),
                    'kind' => ProgrammeSemesterKindEnum::INDUSTRIAL_ATTACHMENT,
                ]);
            }
        }

        return $rows->values();
    }

    private function canUpdateProgrammeSemester(ProgrammeSemester $programmeSemester): bool
    {
        return ! $programmeSemester->studentSemesters()->exists();
    }
}
