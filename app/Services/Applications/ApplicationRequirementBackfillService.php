<?php

declare(strict_types=1);

namespace App\Services\Applications;

use App\Models\Applications\ApplicationCourseRequirement;
use App\Models\Applications\ApplicationLevelRequirement;
use App\Models\Institution\CourseRequirement;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelRequirement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ApplicationRequirementBackfillService
{
    /**
     * @return array{
     *     levels: int,
     *     courses: int,
     *     levels_skipped: int,
     *     courses_skipped: int,
     *     source_level_count: int,
     *     source_course_count: int,
     *     snapshot_path: string|null
     * }
     */
    public function backfill(bool $dryRun = false, bool $fresh = false, bool $snapshot = true): array
    {
        $this->assertLegacyRequirementTablesExist();

        $counts = [
            'levels' => 0,
            'courses' => 0,
            'levels_skipped' => 0,
            'courses_skipped' => 0,
            'source_level_count' => 0,
            'source_course_count' => 0,
            'snapshot_path' => null,
        ];

        if ($snapshot && ! $dryRun) {
            $counts['snapshot_path'] = $this->writeSnapshot();
        }

        if ($fresh && ! $dryRun) {
            $this->wipeApplicationRequirements();
        }

        $levelRows = DepartmentLevelRequirement::query()
            ->whereNull('deleted_at')
            ->get();

        $courseRows = CourseRequirement::query()
            ->whereNull('deleted_at')
            ->get();

        $counts['source_level_count'] = $levelRows->count();
        $counts['source_course_count'] = $courseRows->count();

        foreach ($levelRows as $row) {
            if (! DepartmentLevel::query()->whereKey($row->department_level_id)->exists()) {
                $counts['levels_skipped']++;

                continue;
            }

            $counts['levels']++;

            if ($dryRun) {
                continue;
            }

            ApplicationLevelRequirement::withTrashed()->updateOrCreate(
                [
                    'tenant_id' => (int) $row->tenant_id,
                    'department_level_id' => (int) $row->department_level_id,
                ],
                $this->levelPayloadFromLegacy($row),
            )->restore();
        }

        foreach ($courseRows as $row) {
            if (
                ! DepartmentLevel::query()->whereKey($row->department_level_id)->exists()
                || ! DepartmentCourse::query()->whereKey($row->department_course_id)->exists()
            ) {
                $counts['courses_skipped']++;

                continue;
            }

            $counts['courses']++;

            if ($dryRun) {
                continue;
            }

            ApplicationCourseRequirement::withTrashed()->updateOrCreate(
                [
                    'tenant_id' => (int) $row->tenant_id,
                    'department_level_id' => (int) $row->department_level_id,
                    'department_course_id' => (int) $row->department_course_id,
                ],
                $this->coursePayloadFromLegacy($row),
            )->restore();
        }

        return $counts;
    }

    /**
     * Copy application requirement rows back onto legacy tables (Phase A rollback).
     *
     * @return array{levels: int, courses: int}
     */
    public function restoreLegacyFromApplication(bool $dryRun = false): array
    {
        $this->assertLegacyRequirementTablesExist();

        $counts = ['levels' => 0, 'courses' => 0];

        $levelRows = ApplicationLevelRequirement::query()
            ->whereNull('deleted_at')
            ->get();

        foreach ($levelRows as $row) {
            $counts['levels']++;

            if ($dryRun) {
                continue;
            }

            DepartmentLevelRequirement::withTrashed()->updateOrCreate(
                [
                    'tenant_id' => (int) $row->tenant_id,
                    'department_level_id' => (int) $row->department_level_id,
                ],
                $this->levelPayloadFromApplication($row),
            )->restore();
        }

        $courseRows = ApplicationCourseRequirement::query()
            ->whereNull('deleted_at')
            ->get();

        foreach ($courseRows as $row) {
            $counts['courses']++;

            if ($dryRun) {
                continue;
            }

            CourseRequirement::withTrashed()->updateOrCreate(
                [
                    'tenant_id' => (int) $row->tenant_id,
                    'department_level_id' => (int) $row->department_level_id,
                    'department_course_id' => (int) $row->department_course_id,
                ],
                $this->coursePayloadFromApplication($row),
            )->restore();
        }

        return $counts;
    }

    /**
     * @return array{levels: int, courses: int}
     */
    public function restoreLegacyFromSnapshot(string $path, bool $dryRun = false): array
    {
        $this->assertLegacyRequirementTablesExist();

        $payload = $this->readSnapshot($path);
        $counts = ['levels' => 0, 'courses' => 0];

        foreach ($payload['department_level_requirements'] ?? [] as $row) {
            if (($row['deleted_at'] ?? null) !== null) {
                continue;
            }

            $counts['levels']++;

            if ($dryRun) {
                continue;
            }

            DepartmentLevelRequirement::withTrashed()->updateOrCreate(
                [
                    'tenant_id' => (int) $row['tenant_id'],
                    'department_level_id' => (int) $row['department_level_id'],
                ],
                $this->levelPayloadFromArray($row),
            )->restore();
        }

        foreach ($payload['course_requirements'] ?? [] as $row) {
            if (($row['deleted_at'] ?? null) !== null) {
                continue;
            }

            $counts['courses']++;

            if ($dryRun) {
                continue;
            }

            CourseRequirement::withTrashed()->updateOrCreate(
                [
                    'tenant_id' => (int) $row['tenant_id'],
                    'department_level_id' => (int) $row['department_level_id'],
                    'department_course_id' => (int) $row['department_course_id'],
                ],
                $this->coursePayloadFromArray($row),
            )->restore();
        }

        return $counts;
    }

    public function restoreLegacyFromLatestSnapshot(bool $dryRun = false): array
    {
        $path = $this->latestSnapshotPath();

        if ($path === null) {
            throw new RuntimeException(__('application_requirements.restore_snapshot_missing_latest'));
        }

        return $this->restoreLegacyFromSnapshot($path, $dryRun);
    }

    public function writeSnapshot(): string
    {
        $payload = [
            'generated_at' => Carbon::now()->toIso8601String(),
            'source_level_count' => DepartmentLevelRequirement::withTrashed()->count(),
            'source_course_count' => CourseRequirement::withTrashed()->count(),
            'department_level_requirements' => Schema::hasTable('department_level_requirements')
                ? DB::table('department_level_requirements')->get()->map(fn ($row) => (array) $row)->all()
                : [],
            'course_requirements' => Schema::hasTable('course_requirements')
                ? DB::table('course_requirements')->get()->map(fn ($row) => (array) $row)->all()
                : [],
        ];

        $filename = 'enrolments/requirements-backfill-'.Carbon::now()->format('Ymd_His').'.json';
        Storage::disk('local')->put($filename, json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return Storage::disk('local')->path($filename);
    }

    private function wipeApplicationRequirements(): void
    {
        ApplicationCourseRequirement::query()->forceDelete();
        ApplicationLevelRequirement::query()->forceDelete();
    }

    /**
     * @return array<string, mixed>
     */
    private function levelPayloadFromLegacy(DepartmentLevelRequirement $row): array
    {
        return [
            'is_o_level_required' => (bool) $row->is_o_level_required,
            'required_subjects_count' => $row->required_subjects_count,
            'main_subjects_count' => $row->main_subjects_count,
            'main_subject_ids' => $row->main_subject_ids ?? [],
            'other_subjects_count' => $row->other_subjects_count,
            'only_read_write_required' => (bool) $row->only_read_write_required,
            'required_level_id' => $row->required_level_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coursePayloadFromLegacy(CourseRequirement $row): array
    {
        return [
            'is_o_level_required' => (bool) $row->is_o_level_required,
            'required_subjects_count' => $row->required_subjects_count,
            'main_subjects_count' => $row->main_subjects_count,
            'main_subject_ids' => $row->main_subject_ids ?? [],
            'other_subjects_count' => $row->other_subjects_count,
            'only_read_write_required' => (bool) $row->only_read_write_required,
            'required_level_id' => $row->required_level_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function levelPayloadFromApplication(ApplicationLevelRequirement $row): array
    {
        return [
            'is_o_level_required' => (bool) $row->is_o_level_required,
            'required_subjects_count' => $row->required_subjects_count,
            'main_subjects_count' => $row->main_subjects_count,
            'main_subject_ids' => $row->main_subject_ids ?? [],
            'other_subjects_count' => $row->other_subjects_count,
            'only_read_write_required' => (bool) $row->only_read_write_required,
            'required_level_id' => $row->required_level_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coursePayloadFromApplication(ApplicationCourseRequirement $row): array
    {
        return [
            'is_o_level_required' => (bool) $row->is_o_level_required,
            'required_subjects_count' => $row->required_subjects_count,
            'main_subjects_count' => $row->main_subjects_count,
            'main_subject_ids' => $row->main_subject_ids ?? [],
            'other_subjects_count' => $row->other_subjects_count,
            'only_read_write_required' => (bool) $row->only_read_write_required,
            'required_level_id' => $row->required_level_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function levelPayloadFromArray(array $row): array
    {
        return [
            'is_o_level_required' => (bool) ($row['is_o_level_required'] ?? false),
            'required_subjects_count' => $row['required_subjects_count'] ?? null,
            'main_subjects_count' => $row['main_subjects_count'] ?? null,
            'main_subject_ids' => json_decode((string) ($row['main_subject_ids'] ?? '[]'), true) ?: [],
            'other_subjects_count' => $row['other_subjects_count'] ?? null,
            'only_read_write_required' => (bool) ($row['only_read_write_required'] ?? false),
            'required_level_id' => $row['required_level_id'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function coursePayloadFromArray(array $row): array
    {
        return $this->levelPayloadFromArray($row);
    }

    /**
     * @return array<string, mixed>
     */
    private function readSnapshot(string $path): array
    {
        $resolved = $this->resolveSnapshotPath($path);
        $contents = file_get_contents($resolved);

        if ($contents === false) {
            throw new \InvalidArgumentException(__('application_requirements.restore_snapshot_missing', ['path' => $path]));
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $payload;
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

        throw new \InvalidArgumentException(__('application_requirements.restore_snapshot_missing', ['path' => $path]));
    }

    private function latestSnapshotPath(): ?string
    {
        $files = Storage::disk('local')->files('enrolments');
        $snapshots = array_values(array_filter(
            $files,
            static fn (string $file): bool => str_contains($file, 'requirements-backfill-') && str_ends_with($file, '.json'),
        ));

        if ($snapshots === []) {
            return null;
        }

        rsort($snapshots);

        return Storage::disk('local')->path($snapshots[0]);
    }

    private function assertLegacyRequirementTablesExist(): void
    {
        if (
            ! Schema::hasTable('department_level_requirements')
            || ! Schema::hasTable('course_requirements')
        ) {
            throw new RuntimeException(__('application_requirements.legacy_requirement_tables_removed'));
        }
    }
}
