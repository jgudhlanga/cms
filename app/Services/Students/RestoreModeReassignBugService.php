<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\DepartmentCourse;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentExamResult;
use App\Services\Institution\CourseLevelModeService;
use App\Services\Institution\ProgrammeLinkUsageGuard;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RestoreModeReassignBugService
{
    public function __construct(
        protected CourseLevelModeService $courseLevelModes,
        protected ProgrammeLinkUsageGuard $usageGuard,
    ) {}

    /**
     * @return array{
     *     incidents: array<string, int>,
     *     applications: array{would: int, restored: int, skipped: int},
     *     enrolments: array{would: int, restored: int, skipped: int},
     *     exams: array{would: int, restored: int, skipped: int},
     *     course_level_modes: array{would: int, restored: int, skipped: int},
     * }
     */
    public function run(string $applicationsCsv, ?string $courseLevelModesCsv, bool $dryRun): array
    {
        $applications = $this->readApplicationsCsv($applicationsCsv);
        $courseLevelModes = $courseLevelModesCsv !== null && $courseLevelModesCsv !== ''
            ? $this->readCourseLevelModesCsv($courseLevelModesCsv)
            : [];

        $summary = [
            'incidents' => [],
            'applications' => ['would' => 0, 'restored' => 0, 'skipped' => 0],
            'enrolments' => ['would' => 0, 'restored' => 0, 'skipped' => 0],
            'exams' => ['would' => 0, 'restored' => 0, 'skipped' => 0],
            'course_level_modes' => ['would' => 0, 'restored' => 0, 'skipped' => 0],
        ];

        foreach ($applications as $row) {
            $incident = $row['incident'];
            $summary['incidents'][$incident] = ($summary['incidents'][$incident] ?? 0) + 1;
        }

        if ($dryRun) {
            foreach ($applications as $row) {
                $this->planApplicationRestore($row, $summary);
            }

            foreach ($courseLevelModes as $row) {
                $summary['course_level_modes']['would']++;
            }

            return $summary;
        }

        // Bulk restore must not write Spatie activity_log rows: production MySQL
        // has failed mid-restore when /tmp was missing for those inserts.
        DB::transaction(function () use ($applications, &$summary): void {
            StudentApplication::withoutEvents(function () use ($applications, &$summary): void {
                StudentEnrolment::withoutEvents(function () use ($applications, &$summary): void {
                    StudentExamResult::withoutEvents(function () use ($applications, &$summary): void {
                        CourseLevelMode::withoutEvents(function () use ($applications, $courseLevelModes, &$summary): void {
                            foreach ($applications as $row) {
                                $this->restoreApplication($row, $summary);
                            }

                            foreach ($courseLevelModes as $row) {
                                $this->restoreCourseLevelMode($row, $summary);
                            }

                            $this->syncUsedModes($applications, $courseLevelModes);
                        });
                    });
                });
            });
        });

        return $summary;
    }

    /**
     * @param  array{
     *     incident: string,
     *     application_id: int,
     *     enrolment_ids: list<int>,
     *     old_mode_id: int,
     *     new_mode_id: int,
     *     exam_result_ids: list<int>
     * }  $row
     * @param  array<string, mixed>  $summary
     */
    private function planApplicationRestore(array $row, array &$summary): void
    {
        $application = StudentApplication::query()->find($row['application_id']);

        if ($application === null) {
            $summary['applications']['skipped']++;

            return;
        }

        if ((int) $application->mode_of_study_id === $row['new_mode_id']) {
            $summary['applications']['would']++;
        } else {
            $summary['applications']['skipped']++;
        }

        foreach ($row['enrolment_ids'] as $enrolmentId) {
            $enrolment = StudentEnrolment::query()->find($enrolmentId);

            if ($enrolment === null) {
                $summary['enrolments']['skipped']++;

                continue;
            }

            if ((int) $enrolment->mode_of_study_id === $row['new_mode_id']) {
                $summary['enrolments']['would']++;
            } else {
                $summary['enrolments']['skipped']++;
            }
        }

        foreach ($row['exam_result_ids'] as $examId) {
            $exam = StudentExamResult::query()->find($examId);

            if ($exam === null) {
                $summary['exams']['skipped']++;

                continue;
            }

            if ((int) $exam->mode_of_study_id === $row['new_mode_id']) {
                $summary['exams']['would']++;
            } else {
                $summary['exams']['skipped']++;
            }
        }
    }

    /**
     * @param  array{
     *     application_id: int,
     *     enrolment_ids: list<int>,
     *     old_mode_id: int,
     *     new_mode_id: int,
     *     exam_result_ids: list<int>
     * }  $row
     * @param  array<string, mixed>  $summary
     */
    private function restoreApplication(array $row, array &$summary): void
    {
        $application = StudentApplication::query()->find($row['application_id']);

        if ($application === null) {
            $summary['applications']['skipped']++;

            return;
        }

        if ((int) $application->mode_of_study_id === $row['new_mode_id']) {
            StudentApplication::query()
                ->whereKey($application->id)
                ->where('mode_of_study_id', $row['new_mode_id'])
                ->update([
                    'mode_of_study_id' => $row['old_mode_id'],
                    'updated_at' => now(),
                ]);
            $summary['applications']['restored']++;
        } else {
            $summary['applications']['skipped']++;
        }

        $enrolmentIds = $row['enrolment_ids'];

        if ($enrolmentIds === []) {
            $enrolmentIds = StudentEnrolment::query()
                ->where('student_application_id', $application->id)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
        }

        foreach ($enrolmentIds as $enrolmentId) {
            $enrolment = StudentEnrolment::query()->find($enrolmentId);

            if ($enrolment === null) {
                $summary['enrolments']['skipped']++;

                continue;
            }

            if ((int) $enrolment->mode_of_study_id === $row['new_mode_id']) {
                StudentEnrolment::query()
                    ->whereKey($enrolment->id)
                    ->where('mode_of_study_id', $row['new_mode_id'])
                    ->update([
                        'mode_of_study_id' => $row['old_mode_id'],
                        'updated_at' => now(),
                    ]);
                $summary['enrolments']['restored']++;
            } else {
                $summary['enrolments']['skipped']++;
            }
        }

        foreach ($row['exam_result_ids'] as $examId) {
            $exam = StudentExamResult::query()->find($examId);

            if ($exam === null) {
                $summary['exams']['skipped']++;

                continue;
            }

            if ((int) $exam->mode_of_study_id === $row['new_mode_id']) {
                StudentExamResult::query()
                    ->whereKey($exam->id)
                    ->where('mode_of_study_id', $row['new_mode_id'])
                    ->update([
                        'mode_of_study_id' => $row['old_mode_id'],
                        'updated_at' => now(),
                    ]);
                $summary['exams']['restored']++;
            } else {
                $summary['exams']['skipped']++;
            }
        }
    }

    /**
     * @param  array{
     *     course_level_mode_id: int,
     *     restore_modes_json: list<int>,
     *     action: string
     * }  $row
     * @param  array<string, mixed>  $summary
     */
    private function restoreCourseLevelMode(array $row, array &$summary): void
    {
        $mode = CourseLevelMode::withTrashed()->find($row['course_level_mode_id']);

        if ($mode === null) {
            $summary['course_level_modes']['skipped']++;

            return;
        }

        match ($row['action']) {
            'update' => $this->updateCourseLevelModeModes($mode, $row['restore_modes_json']),
            'soft_delete' => $this->softDeleteCourseLevelMode($mode),
            'restore_soft_deleted' => $this->restoreSoftDeletedCourseLevelMode($mode, $row['restore_modes_json']),
            default => throw new RuntimeException('Unknown course level mode action: '.$row['action']),
        };

        $summary['course_level_modes']['restored']++;
    }

    private function softDeleteCourseLevelMode(CourseLevelMode $mode): void
    {
        if (! $mode->trashed()) {
            CourseLevelMode::query()
                ->whereKey($mode->id)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * @param  list<int>  $modes
     */
    private function updateCourseLevelModeModes(CourseLevelMode $mode, array $modes): void
    {
        CourseLevelMode::withTrashed()
            ->whereKey($mode->id)
            ->update([
                'modes' => json_encode(array_values($modes)),
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  list<int>  $modes
     */
    private function restoreSoftDeletedCourseLevelMode(CourseLevelMode $mode, array $modes): void
    {
        CourseLevelMode::withTrashed()
            ->whereKey($mode->id)
            ->update([
                'modes' => json_encode(array_values($modes)),
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  list<array{department_course_id?: int, department_level_id?: int}>  $applications
     * @param  list<array{department_course_id: int, department_level_id: int}>  $courseLevelModes
     */
    private function syncUsedModes(array $applications, array $courseLevelModes): void
    {
        $pairs = [];

        foreach ($applications as $row) {
            $application = StudentApplication::query()->find($row['application_id'] ?? 0);

            if ($application === null) {
                continue;
            }

            $key = (int) $application->department_course_id.'-'.(int) $application->department_level_id;
            $pairs[$key] = [
                (int) $application->department_course_id,
                (int) $application->department_level_id,
            ];
        }

        foreach ($courseLevelModes as $row) {
            $key = $row['department_course_id'].'-'.$row['department_level_id'];
            $pairs[$key] = [$row['department_course_id'], $row['department_level_id']];
        }

        foreach ($pairs as [$courseId, $levelId]) {
            $used = $this->usageGuard->usedModeIds($courseId, $levelId);

            foreach ($used as $modeId) {
                $this->courseLevelModes->ensureMode($courseId, $levelId, $modeId);
            }

            if ($used === []) {
                continue;
            }

            $departmentCourse = DepartmentCourse::query()->find($courseId);

            if ($departmentCourse === null) {
                continue;
            }

            $row = CourseLevelMode::query()
                ->where('department_course_id', $courseId)
                ->where('department_level_id', $levelId)
                ->first();

            if ($row === null) {
                continue;
            }

            $current = array_values(array_unique(array_map('intval', $row->modes ?? [])));
            $merged = array_values(array_unique([...$current, ...$used]));

            if ($merged !== $current) {
                $row->update(['modes' => $merged]);
            }
        }
    }

    /**
     * @return list<array{
     *     incident: string,
     *     application_id: int,
     *     enrolment_ids: list<int>,
     *     old_mode_id: int,
     *     new_mode_id: int,
     *     exam_result_ids: list<int>
     * }>
     */
    private function readApplicationsCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to open applications CSV: '.$path);
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            throw new RuntimeException('Applications CSV is empty: '.$path);
        }

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($header, $data);

            if ($row === false) {
                continue;
            }

            $rows[] = [
                'incident' => (string) $row['incident'],
                'application_id' => (int) $row['application_id'],
                'enrolment_ids' => $this->parseIdList((string) ($row['enrolment_ids'] ?? '')),
                'old_mode_id' => (int) $row['old_mode_id'],
                'new_mode_id' => (int) $row['new_mode_id'],
                'exam_result_ids' => $this->parseIdList((string) ($row['exam_result_ids'] ?? '')),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return list<array{
     *     incident: string,
     *     course_level_mode_id: int,
     *     department_course_id: int,
     *     department_level_id: int,
     *     restore_modes_json: list<int>,
     *     action: string
     * }>
     */
    private function readCourseLevelModesCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to open course level modes CSV: '.$path);
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            throw new RuntimeException('Course level modes CSV is empty: '.$path);
        }

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($header, $data);

            if ($row === false) {
                continue;
            }

            $modes = json_decode((string) $row['restore_modes_json'], true);

            $rows[] = [
                'incident' => (string) $row['incident'],
                'course_level_mode_id' => (int) $row['course_level_mode_id'],
                'department_course_id' => (int) $row['department_course_id'],
                'department_level_id' => (int) $row['department_level_id'],
                'restore_modes_json' => array_values(array_map('intval', is_array($modes) ? $modes : [])),
                'action' => (string) $row['action'],
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return list<int>
     */
    private function parseIdList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('intval', preg_split('/[;,\s]+/', $value) ?: []),
            fn (int $id): bool => $id > 0,
        ));
    }
}
