<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\DepartmentCourse;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;
use Illuminate\Support\Facades\DB;

class CourseLevelModeService
{
    public function __construct(
        protected ProgrammeLinkUsageGuard $usageGuard,
        protected IDepartmentCourseRepository $departmentCourses,
    ) {}

    /**
     * @param  array<int|string, list<int|string>>  $modeIdsByLevel
     */
    public function sync(DepartmentCourse $departmentCourse, array $modeIdsByLevel): void
    {
        $linkedLevelIds = $this->linkedLevelIds($departmentCourse);

        DB::transaction(function () use ($departmentCourse, $modeIdsByLevel, $linkedLevelIds): void {
            foreach ($modeIdsByLevel as $levelId => $modes) {
                $departmentLevelId = (int) $levelId;

                if ($departmentLevelId < 1 || ! in_array($departmentLevelId, $linkedLevelIds, true)) {
                    continue;
                }

                $proposedModeIds = $this->normalizeIds($modes);
                $this->assertProposedModesKeepUsage($departmentCourse, $departmentLevelId, $proposedModeIds);
                $this->writeRow($departmentCourse, $departmentLevelId, $proposedModeIds);
            }

            $this->pruneUnusedOrphans($departmentCourse);
        });
    }

    /**
     * @return array{pruned: int, restored: int, modes_stripped: int}
     */
    public function repairOrphans(bool $dryRun = true): array
    {
        $summary = ['pruned' => 0, 'restored' => 0, 'modes_stripped' => 0];

        $orphans = CourseLevelMode::query()
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('department_level_courses as dlc')
                    ->whereColumn('dlc.department_course_id', 'course_level_modes.department_course_id')
                    ->whereColumn('dlc.department_level_id', 'course_level_modes.department_level_id');
            })
            ->get();

        foreach ($orphans as $orphan) {
            $departmentCourseId = (int) $orphan->department_course_id;
            $departmentLevelId = (int) $orphan->department_level_id;
            $used = $this->usageGuard->courseLevelUsage($departmentCourseId, [$departmentLevelId]);

            if ($used === []) {
                $summary['pruned']++;

                if (! $dryRun) {
                    $orphan->delete();
                }

                continue;
            }

            $keptModeIds = $this->usageGuard->usedModeIds($departmentCourseId, $departmentLevelId);
            $currentModeIds = $this->normalizeIds($orphan->modes ?? []);
            $stripped = count(array_diff($currentModeIds, $keptModeIds));

            $summary['restored']++;
            $summary['modes_stripped'] += $stripped;

            if ($dryRun) {
                continue;
            }

            $departmentCourse = DepartmentCourse::query()->find($departmentCourseId);

            if ($departmentCourse === null) {
                continue;
            }

            $this->departmentCourses->ensureCourseLevel($departmentCourse, $departmentLevelId);
            $this->writeRow($departmentCourse, $departmentLevelId, $keptModeIds);
        }

        return $summary;
    }

    /**
     * @return list<array{course: string, level: string, action: string, records: int}>
     */
    public function orphanPlan(): array
    {
        return CourseLevelMode::query()
            ->with(['departmentCourse.course', 'departmentLevel.level'])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('department_level_courses as dlc')
                    ->whereColumn('dlc.department_course_id', 'course_level_modes.department_course_id')
                    ->whereColumn('dlc.department_level_id', 'course_level_modes.department_level_id');
            })
            ->get()
            ->map(function (CourseLevelMode $orphan): array {
                $used = $this->usageGuard->courseLevelUsage(
                    (int) $orphan->department_course_id,
                    [(int) $orphan->department_level_id],
                );
                $records = $used[(int) $orphan->department_level_id] ?? 0;

                return [
                    'course' => (string) ($orphan->departmentCourse?->course?->name ?? $orphan->department_course_id),
                    'level' => (string) ($orphan->departmentLevel?->level?->name ?? $orphan->department_level_id),
                    'action' => $records > 0 ? 'restore' : 'prune',
                    'records' => $records,
                ];
            })
            ->values()
            ->all();
    }

    private function pruneUnusedOrphans(DepartmentCourse $departmentCourse): void
    {
        $linkedLevelIds = $this->linkedLevelIds($departmentCourse);

        $departmentCourse->courseLevelModes()
            ->whereNotIn('department_level_id', $linkedLevelIds === [] ? [0] : $linkedLevelIds)
            ->get()
            ->each(function (CourseLevelMode $orphan) use ($departmentCourse): void {
                $used = $this->usageGuard->courseLevelUsage(
                    (int) $departmentCourse->id,
                    [(int) $orphan->department_level_id],
                );

                if ($used !== []) {
                    return;
                }

                $orphan->delete();
            });
    }

    /**
     * @param  list<int>  $proposedModeIds
     */
    private function assertProposedModesKeepUsage(
        DepartmentCourse $departmentCourse,
        int $departmentLevelId,
        array $proposedModeIds,
    ): void {
        $usedModeIds = $this->usageGuard->usedModeIds((int) $departmentCourse->id, $departmentLevelId);

        if ($usedModeIds === []) {
            if ($proposedModeIds === []) {
                $this->usageGuard->assertCourseLevelsUnused(
                    (int) $departmentCourse->id,
                    [$departmentLevelId],
                    'mode_ids',
                    'trans.programme_course_level_clear_modes_in_use',
                );
            }

            return;
        }

        $removed = array_values(array_diff($usedModeIds, $proposedModeIds));

        $this->usageGuard->assertCourseLevelModesUnused(
            (int) $departmentCourse->id,
            $departmentLevelId,
            $removed,
        );
    }

    /**
     * @param  list<int>  $modeIds
     */
    private function writeRow(DepartmentCourse $departmentCourse, int $departmentLevelId, array $modeIds): void
    {
        $existing = CourseLevelMode::withTrashed()
            ->where('department_course_id', $departmentCourse->id)
            ->where('department_level_id', $departmentLevelId)
            ->orderByRaw('deleted_at is not null')
            ->orderByDesc('id')
            ->get();

        $keep = $existing->first();

        if ($modeIds === []) {
            $existing->each->delete();

            return;
        }

        $existing
            ->reject(fn (CourseLevelMode $row): bool => $keep !== null && $row->id === $keep->id)
            ->each
            ->delete();

        if ($keep === null) {
            CourseLevelMode::query()->create([
                'department_course_id' => $departmentCourse->id,
                'department_level_id' => $departmentLevelId,
                'modes' => array_values($modeIds),
            ]);

            return;
        }

        if ($keep->trashed()) {
            $keep->restore();
        }

        $keep->update(['modes' => array_values($modeIds)]);
    }

    /**
     * @return list<int>
     */
    private function linkedLevelIds(DepartmentCourse $departmentCourse): array
    {
        return $departmentCourse->departmentCourseLevels()
            ->pluck('department_level_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function normalizeIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn (int $id): bool => $id > 0,
        )));
    }
}
