<?php

declare(strict_types=1);

namespace App\Services\Applications;

use App\Models\Applications\ApplicationOfferingCourse;
use App\Models\Applications\ApplicationOfferingDepartment;
use App\Models\Applications\ApplicationOfferingLevel;
use App\Models\Applications\ApplicationOfferingMode;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ApplicationOfferingBackfillService
{
    /**
     * @return array{
     *     departments: int,
     *     departments_skipped: int,
     *     levels: int,
     *     courses: int,
     *     modes: int,
     *     courses_skipped: int,
     *     snapshot_path: string|null
     * }
     */
    public function backfill(bool $dryRun = false, bool $fresh = false, bool $snapshot = true): array
    {
        $this->assertLegacyFlagColumnsExist();

        $counts = [
            'departments' => 0,
            'departments_skipped' => 0,
            'levels' => 0,
            'courses' => 0,
            'modes' => 0,
            'courses_skipped' => 0,
            'snapshot_path' => null,
        ];

        if ($snapshot && ! $dryRun) {
            $counts['snapshot_path'] = $this->writeSnapshot();
        }

        if ($fresh && ! $dryRun) {
            $this->wipeOfferings();
        }

        $departmentLevels = DepartmentLevel::query()
            ->with(['institutionDepartment'])
            ->where('show_on_current_application_period', true)
            ->whereHas('institutionDepartment')
            ->get();

        $offeredDepartmentIds = [];

        foreach ($departmentLevels as $departmentLevel) {
            $institutionDepartment = $departmentLevel->institutionDepartment;
            if ($institutionDepartment === null) {
                continue;
            }

            $tenantId = (int) $institutionDepartment->tenant_id;
            $offeringDepartment = $this->ensureOfferingDepartment(
                $institutionDepartment,
                $dryRun,
                $counts,
                $offeredDepartmentIds,
            );

            if ($offeringDepartment === null && ! $dryRun) {
                continue;
            }

            $levelCourses = DepartmentLevelCourse::query()
                ->where('department_level_id', $departmentLevel->id)
                ->with('departmentCourse')
                ->get();

            $offeringLevelId = null;
            $levelCounted = false;

            foreach ($levelCourses as $levelCourse) {
                $departmentCourse = $levelCourse->departmentCourse;
                if ($departmentCourse === null) {
                    continue;
                }

                if (! $departmentCourse->show_on_current_application_period) {
                    continue;
                }

                $modeIds = $this->modeIdsForPair(
                    (int) $departmentCourse->id,
                    (int) $departmentLevel->id,
                );

                if ($modeIds === []) {
                    $counts['courses_skipped']++;

                    continue;
                }

                if (! $levelCounted) {
                    $counts['levels']++;
                    $levelCounted = true;
                }

                $counts['courses']++;
                $counts['modes'] += count($modeIds);

                if ($dryRun) {
                    continue;
                }

                if ($offeringLevelId === null) {
                    $offeringLevel = ApplicationOfferingLevel::query()->updateOrCreate(
                        [
                            'application_offering_department_id' => $offeringDepartment->id,
                            'department_level_id' => $departmentLevel->id,
                        ],
                        ['tenant_id' => $tenantId],
                    );
                    $offeringLevelId = (int) $offeringLevel->id;
                }

                $offeringCourse = ApplicationOfferingCourse::query()->updateOrCreate(
                    [
                        'application_offering_level_id' => $offeringLevelId,
                        'department_course_id' => $departmentCourse->id,
                    ],
                    ['tenant_id' => $tenantId],
                );

                foreach ($modeIds as $modeId) {
                    ApplicationOfferingMode::query()->updateOrCreate(
                        [
                            'application_offering_course_id' => $offeringCourse->id,
                            'mode_of_study_id' => $modeId,
                        ],
                        ['tenant_id' => $tenantId],
                    );
                }
            }
        }

        return $counts;
    }

    /**
     * @return array{departments: int, levels: int, courses: int}
     */
    public function restoreFlagsFromOfferings(bool $dryRun = false): array
    {
        $this->assertLegacyFlagColumnsExist();

        $counts = ['departments' => 0, 'levels' => 0, 'courses' => 0];

        $offerings = ApplicationOfferingDepartment::query()
            ->with(['levels.courses', 'institutionDepartment'])
            ->get();

        $offeredDepartmentIds = $offerings->pluck('institution_department_id')->map(fn ($id) => (int) $id)->all();
        $offeredLevelIds = [];
        $offeredCourseIds = [];

        foreach ($offerings as $offering) {
            $counts['departments']++;

            if (! $dryRun) {
                InstitutionDepartment::query()
                    ->whereKey($offering->institution_department_id)
                    ->update([
                        'has_apprentice_courses' => (bool) $offering->has_apprentice_programmes,
                    ]);
            }

            foreach ($offering->levels as $level) {
                $offeredLevelIds[] = (int) $level->department_level_id;
                $counts['levels']++;

                if (! $dryRun) {
                    DepartmentLevel::query()
                        ->whereKey($level->department_level_id)
                        ->update(['show_on_current_application_period' => true]);
                }

                foreach ($level->courses as $course) {
                    $offeredCourseIds[] = (int) $course->department_course_id;
                    $counts['courses']++;

                    if (! $dryRun) {
                        DepartmentCourse::query()
                            ->whereKey($course->department_course_id)
                            ->update(['show_on_current_application_period' => true]);
                    }
                }
            }
        }

        if (! $dryRun) {
            if ($offeredLevelIds !== []) {
                DepartmentLevel::query()
                    ->whereNotIn('id', array_unique($offeredLevelIds))
                    ->update(['show_on_current_application_period' => false]);
            } else {
                DepartmentLevel::query()->update(['show_on_current_application_period' => false]);
            }

            if ($offeredCourseIds !== []) {
                DepartmentCourse::query()
                    ->whereNotIn('id', array_unique($offeredCourseIds))
                    ->update(['show_on_current_application_period' => false]);
            } else {
                DepartmentCourse::query()->update(['show_on_current_application_period' => false]);
            }

            if ($offeredDepartmentIds !== []) {
                InstitutionDepartment::query()
                    ->whereNotIn('id', $offeredDepartmentIds)
                    ->update(['has_apprentice_courses' => false]);
            }
        }

        return $counts;
    }

    /**
     * @return array{departments: int, levels: int, courses: int}
     */
    public function restoreFlagsFromSnapshot(string $path, bool $dryRun = false): array
    {
        $this->assertLegacyFlagColumnsExist();

        $absolute = $this->resolveSnapshotPath($path);
        $payload = json_decode((string) file_get_contents($absolute), true, 512, JSON_THROW_ON_ERROR);

        $counts = ['departments' => 0, 'levels' => 0, 'courses' => 0];

        $departmentFlags = $payload['institution_departments'] ?? [];
        $levelFlags = $payload['department_levels'] ?? [];
        $courseFlags = $payload['department_courses'] ?? [];

        $counts['departments'] = count($departmentFlags);
        $counts['levels'] = count($levelFlags);
        $counts['courses'] = count($courseFlags);

        if ($dryRun) {
            return $counts;
        }

        foreach ($departmentFlags as $row) {
            InstitutionDepartment::query()
                ->whereKey((int) $row['id'])
                ->update([
                    'has_apprentice_courses' => (bool) ($row['has_apprentice_courses'] ?? false),
                ]);
        }

        foreach ($levelFlags as $row) {
            DepartmentLevel::query()
                ->whereKey((int) $row['id'])
                ->update([
                    'show_on_current_application_period' => (bool) ($row['show_on_current_application_period'] ?? false),
                ]);
        }

        foreach ($courseFlags as $row) {
            DepartmentCourse::query()
                ->whereKey((int) $row['id'])
                ->update([
                    'show_on_current_application_period' => (bool) ($row['show_on_current_application_period'] ?? false),
                ]);
        }

        return $counts;
    }

    public function writeSnapshot(): string
    {
        $this->assertLegacyFlagColumnsExist();

        $timestamp = Carbon::now()->format('YmdHis');
        $relative = "enrolments/backfill-{$timestamp}.json";

        $payload = [
            'created_at' => Carbon::now()->toIso8601String(),
            'institution_departments' => InstitutionDepartment::query()
                ->get(['id', 'has_apprentice_courses'])
                ->map(fn (InstitutionDepartment $row): array => [
                    'id' => (int) $row->id,
                    'has_apprentice_courses' => (bool) $row->has_apprentice_courses,
                ])
                ->values()
                ->all(),
            'department_levels' => DepartmentLevel::query()
                ->get(['id', 'show_on_current_application_period'])
                ->map(fn (DepartmentLevel $row): array => [
                    'id' => (int) $row->id,
                    'show_on_current_application_period' => (bool) $row->show_on_current_application_period,
                ])
                ->values()
                ->all(),
            'department_courses' => DepartmentCourse::query()
                ->get(['id', 'show_on_current_application_period'])
                ->map(fn (DepartmentCourse $row): array => [
                    'id' => (int) $row->id,
                    'show_on_current_application_period' => (bool) $row->show_on_current_application_period,
                ])
                ->values()
                ->all(),
            'offerings' => ApplicationOfferingDepartment::query()
                ->with(['levels.courses.modes'])
                ->get()
                ->toArray(),
        ];

        Storage::disk('local')->put($relative, json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return Storage::disk('local')->path($relative);
    }

    private function wipeOfferings(): void
    {
        DB::transaction(function (): void {
            DB::table('application_offering_modes')->delete();
            DB::table('application_offering_courses')->delete();
            DB::table('application_offering_levels')->delete();
            DB::table('application_offering_departments')->delete();
        });
    }

    /**
     * @param  array{departments: int, departments_skipped: int}  $counts
     * @param  list<int>  $offeredDepartmentIds
     */
    private function ensureOfferingDepartment(
        InstitutionDepartment $institutionDepartment,
        bool $dryRun,
        array &$counts,
        array &$offeredDepartmentIds,
    ): ?ApplicationOfferingDepartment {
        $id = (int) $institutionDepartment->id;

        if (in_array($id, $offeredDepartmentIds, true)) {
            if ($dryRun) {
                return null;
            }

            return ApplicationOfferingDepartment::withTrashed()
                ->where('institution_department_id', $id)
                ->first();
        }

        $offeredDepartmentIds[] = $id;

        $existing = ApplicationOfferingDepartment::withTrashed()
            ->where('institution_department_id', $id)
            ->first();

        if ($existing !== null) {
            $counts['departments_skipped']++;
        } else {
            $counts['departments']++;
        }

        if ($dryRun) {
            return null;
        }

        $offering = ApplicationOfferingDepartment::withTrashed()->updateOrCreate(
            [
                'tenant_id' => (int) $institutionDepartment->tenant_id,
                'institution_department_id' => $id,
            ],
            [
                'has_apprentice_programmes' => (bool) $institutionDepartment->has_apprentice_courses,
            ],
        );

        if ($offering->trashed()) {
            $offering->restore();
        }

        return $offering;
    }

    /**
     * @return list<int>
     */
    private function modeIdsForPair(int $departmentCourseId, int $departmentLevelId): array
    {
        $row = CourseLevelMode::query()
            ->where('department_course_id', $departmentCourseId)
            ->where('department_level_id', $departmentLevelId)
            ->first();

        return array_values(array_unique(array_filter(
            array_map('intval', $row?->modes ?? []),
            static fn (int $id): bool => $id > 0,
        )));
    }

    private function resolveSnapshotPath(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        $storagePath = Storage::disk('local')->path($path);
        if (is_file($storagePath)) {
            return $storagePath;
        }

        throw new \InvalidArgumentException(__('application_offerings.restore_snapshot_missing', ['path' => $path]));
    }

    private function assertLegacyFlagColumnsExist(): void
    {
        if (
            ! Schema::hasColumn('department_levels', 'show_on_current_application_period')
            || ! Schema::hasColumn('department_courses', 'show_on_current_application_period')
            || ! Schema::hasColumn('institution_departments', 'has_apprentice_courses')
        ) {
            throw new RuntimeException(__('application_offerings.legacy_flag_columns_removed'));
        }
    }
}
