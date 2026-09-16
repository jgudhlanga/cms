<?php

declare(strict_types=1);

namespace App\Actions\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\ProgrammeSemesterKindEnum;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Institution\ProgrammeStage;
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
            $levelName = trim((string) $departmentLevelCourse->departmentLevel?->level?->name);

            $desired = $this->buildDesiredRows(
                $calendarType,
                $periodsPerYear,
                $taughtCount,
                $attachmentCount,
                $levelName,
            );

            $stagesByNumber = $this->syncStages($departmentLevelCourse, $desired, $levelName);
            $existing = $this->existingByPosition($departmentLevelCourse);
            $synced = collect();

            foreach ($desired as $row) {
                $stageNumber = (int) $row['year_number'];
                $row['programme_stage_id'] = $stagesByNumber->get($stageNumber)?->id;
                $synced->push($this->syncRow($existing, $departmentLevelCourse, $row));
            }

            $this->purgeUnusedPositions(
                $departmentLevelCourse,
                $desired->pluck('position')->map(fn (mixed $position): int => (int) $position)->all(),
            );

            $this->purgeUnusedStages(
                $departmentLevelCourse,
                $desired->pluck('year_number')->unique()->map(fn (mixed $number): int => (int) $number)->all(),
            );

            return $synced->sortBy('position')->values();
        });
    }

    /**
     * @return Collection<int, array{
     *     position: int,
     *     name: string,
     *     kind: ProgrammeSemesterKindEnum,
     *     year_number: int,
     *     period_in_year: int
     * }>
     */
    private function buildDesiredRows(
        AcademicCalendarTypeEnum $calendarType,
        int $periodsPerYear,
        int $taughtCount,
        int $attachmentCount,
        string $levelName,
    ): Collection {
        $rows = collect();
        $position = 1;

        for ($taughtIndex = 1; $taughtIndex <= $taughtCount; $taughtIndex++) {
            $yearNumber = (int) ceil($taughtIndex / $periodsPerYear);
            $periodInYear = (($taughtIndex - 1) % $periodsPerYear) + 1;

            $rows->push([
                'position' => $position++,
                'name' => ProgrammeSemesterNameFormatter::taughtName(
                    $calendarType,
                    $yearNumber,
                    $periodInYear,
                    $levelName,
                ),
                'kind' => ProgrammeSemesterKindEnum::TAUGHT,
                'year_number' => $yearNumber,
                'period_in_year' => $periodInYear,
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
                    'name' => ProgrammeSemesterNameFormatter::attachmentName(
                        $yearNumber,
                        $periodInYear,
                        $levelName,
                        $calendarType,
                    ),
                    'kind' => ProgrammeSemesterKindEnum::INDUSTRIAL_ATTACHMENT,
                    'year_number' => $yearNumber,
                    'period_in_year' => $periodInYear,
                ]);
            }
        }

        return $rows->values();
    }

    /**
     * @param  Collection<int, array{year_number: int, kind: ProgrammeSemesterKindEnum}>  $desired
     * @return Collection<int, ProgrammeStage>
     */
    private function syncStages(
        DepartmentLevelCourse $departmentLevelCourse,
        Collection $desired,
        string $levelName,
    ): Collection {
        $existing = ProgrammeStage::query()
            ->withTrashed()
            ->where('department_level_course_id', $departmentLevelCourse->id)
            ->get()
            ->keyBy(fn (ProgrammeStage $stage): int => (int) $stage->stage_number);

        $synced = collect();

        foreach ($desired->groupBy('year_number') as $stageNumber => $rows) {
            $stageNumber = (int) $stageNumber;
            $kinds = $rows->pluck('kind')->unique();
            $kind = $kinds->count() === 1 && $kinds->first() === ProgrammeSemesterKindEnum::INDUSTRIAL_ATTACHMENT
                ? ProgrammeSemesterKindEnum::INDUSTRIAL_ATTACHMENT
                : ProgrammeSemesterKindEnum::TAUGHT;

            $attributes = [
                'department_level_course_id' => $departmentLevelCourse->id,
                'position' => $stageNumber,
                'stage_number' => $stageNumber,
                'code' => ProgrammeSemesterNameFormatter::stageCode($levelName, $stageNumber),
                'name' => ProgrammeSemesterNameFormatter::stageName($levelName, $stageNumber),
                'kind' => $kind,
            ];

            $current = $existing->get($stageNumber);

            if (! $current instanceof ProgrammeStage) {
                $synced->put($stageNumber, ProgrammeStage::query()->create($attributes));

                continue;
            }

            if ($current->trashed()) {
                $current->restore();
            }

            $current->update($attributes);
            $synced->put($stageNumber, $current->fresh() ?? $current);
        }

        return $synced;
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
     * @param  array{
     *     position: int,
     *     name: string,
     *     kind: ProgrammeSemesterKindEnum,
     *     year_number: int,
     *     period_in_year: int,
     *     programme_stage_id: int|null
     * }  $row
     */
    private function syncRow(
        Collection $existing,
        DepartmentLevelCourse $departmentLevelCourse,
        array $row,
    ): ProgrammeSemester {
        $position = (int) $row['position'];
        $current = $existing->get($position);
        $attributes = [
            'department_level_course_id' => $departmentLevelCourse->id,
            'programme_stage_id' => $row['programme_stage_id'],
            'position' => $position,
            'year_number' => $row['year_number'],
            'period_in_year' => $row['period_in_year'],
            'name' => $row['name'],
            'kind' => $row['kind'],
        ];

        if (! $current instanceof ProgrammeSemester) {
            return ProgrammeSemester::query()->create($attributes);
        }

        if ($current->trashed()) {
            $current->restore();
        }

        if ($this->canRestructureProgrammeSemester($current)) {
            $current->update($attributes);
        } else {
            $current->update([
                'programme_stage_id' => $row['programme_stage_id'],
                'year_number' => $row['year_number'],
                'period_in_year' => $row['period_in_year'],
                'name' => $row['name'],
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

    /**
     * @param  list<int>  $desiredStageNumbers
     */
    private function purgeUnusedStages(DepartmentLevelCourse $departmentLevelCourse, array $desiredStageNumbers): void
    {
        ProgrammeStage::query()
            ->withTrashed()
            ->where('department_level_course_id', $departmentLevelCourse->id)
            ->whereNotIn('stage_number', $desiredStageNumbers)
            ->whereDoesntHave('programmeSemesters')
            ->whereDoesntHave('studentProgrammeStages')
            ->forceDelete();
    }

    private function canRestructureProgrammeSemester(ProgrammeSemester $programmeSemester): bool
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
