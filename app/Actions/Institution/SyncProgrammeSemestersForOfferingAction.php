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
            $calendarType = $this->calendarType($departmentLevelCourse);
            $periodsPerYear = ProgrammeSemesterNameFormatter::periodsPerYear($calendarType);
            $taughtCount = max(1, (int) $departmentLevelCourse->taught_semester_count);
            $includesAttachment = (bool) $departmentLevelCourse->includes_industrial_attachment;
            $attachmentCount = $includesAttachment
                ? max(1, (int) $departmentLevelCourse->attachment_semester_count)
                : 0;

            $desired = $this->buildDesiredRows(
                $calendarType,
                $periodsPerYear,
                $taughtCount,
                $attachmentCount,
            );

            $existing = $this->existingByPosition($departmentLevelCourse);
            $synced = collect();

            foreach ($desired as $row) {
                $synced->push($this->syncRow($existing, $departmentLevelCourse, $row));
            }

            $this->purgeUnusedPositions(
                $departmentLevelCourse,
                $desired->pluck('position')->map(fn (mixed $position): int => (int) $position)->all(),
            );

            return $synced->sortBy('position')->values();
        });
    }

    /**
     * @return Collection<int, array{position: int, name: string, kind: ProgrammeSemesterKindEnum}>
     */
    private function buildDesiredRows(
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

    /**
     * @return Collection<int, ProgrammeSemester>
     */
    private function existingByPosition(DepartmentLevelCourse $departmentLevelCourse): Collection
    {
        return ProgrammeSemester::query()
            ->withTrashed()
            ->with('studentSemesters')
            ->where('department_level_course_id', $departmentLevelCourse->id)
            ->orderBy('position')
            ->get()
            ->keyBy(fn (ProgrammeSemester $semester): int => (int) $semester->position);
    }

    /**
     * @param  Collection<int, ProgrammeSemester>  $existing
     * @param  array{position: int, name: string, kind: ProgrammeSemesterKindEnum}  $row
     */
    private function syncRow(
        Collection $existing,
        DepartmentLevelCourse $departmentLevelCourse,
        array $row,
    ): ProgrammeSemester {
        $position = (int) $row['position'];
        $current = $existing->get($position);

        if (! $current instanceof ProgrammeSemester) {
            return ProgrammeSemester::query()->create([
                'department_level_course_id' => $departmentLevelCourse->id,
                'position' => $position,
                'name' => $row['name'],
                'kind' => $row['kind'],
            ]);
        }

        if ($current->trashed()) {
            $current->restore();
        }

        if ($this->canUpdateProgrammeSemester($current)) {
            $current->update([
                'name' => $row['name'],
                'kind' => $row['kind'],
            ]);
        }

        return $current->fresh() ?? $current;
    }

    /**
     * @param  list<int>  $desiredPositions
     */
    private function purgeUnusedPositions(DepartmentLevelCourse $departmentLevelCourse, array $desiredPositions): void
    {
        ProgrammeSemester::query()
            ->withTrashed()
            ->where('department_level_course_id', $departmentLevelCourse->id)
            ->whereNotIn('position', $desiredPositions)
            ->whereDoesntHave('studentSemesters')
            ->forceDelete();
    }

    private function canUpdateProgrammeSemester(ProgrammeSemester $programmeSemester): bool
    {
        if ($programmeSemester->relationLoaded('studentSemesters')) {
            return $programmeSemester->studentSemesters->isEmpty();
        }

        return ! $programmeSemester->studentSemesters()->exists();
    }

    private function calendarType(DepartmentLevelCourse $departmentLevelCourse): AcademicCalendarTypeEnum
    {
        $calendarType = $departmentLevelCourse->departmentLevel?->level?->calendar_type
            ?? AcademicCalendarTypeEnum::SEMESTER;

        if ($calendarType instanceof AcademicCalendarTypeEnum) {
            return $calendarType;
        }

        return AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
            ?? AcademicCalendarTypeEnum::SEMESTER;
    }
}
