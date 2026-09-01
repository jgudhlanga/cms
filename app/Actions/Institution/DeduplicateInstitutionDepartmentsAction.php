<?php

declare(strict_types=1);

namespace App\Actions\Institution;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeduplicateInstitutionDepartmentsAction
{
    /**
     * @var list<array{table: string, unique: list<string>}>
     */
    private const REMAP_TARGETS = [
        ['table' => 'institution_department_staff', 'unique' => ['staff_id', 'institution_department_id']],
        ['table' => 'department_courses', 'unique' => ['institution_department_id', 'course_id']],
        ['table' => 'department_levels', 'unique' => ['institution_department_id', 'level_id']],
        ['table' => 'institution_department_metadata', 'unique' => ['institution_department_id']],
        ['table' => 'department_intake_class_sizes', 'unique' => []],
        ['table' => 'department_application_steps', 'unique' => []],
        ['table' => 'class_configs', 'unique' => [
            'institution_department_id', 'department_course_id', 'department_level_id',
            'mode_of_study_id', 'calendar_year', 'semester_id',
        ]],
        ['table' => 'course_syllabuses', 'unique' => []],
        ['table' => 'course_syllabus_import_logs', 'unique' => []],
        ['table' => 'student_applications', 'unique' => []],
        ['table' => 'student_enrolments', 'unique' => []],
        ['table' => 'student_exam_results', 'unique' => []],
        ['table' => 'application_offering_departments', 'unique' => ['tenant_id', 'institution_department_id']],
    ];

    /**
     * @return list<array{
     *     tenant_id: int,
     *     department_id: int,
     *     keeper_id: int,
     *     duplicate_ids: list<int>,
     * }>
     */
    public function plan(): array
    {
        $rows = DB::table('institution_departments')
            ->select(['id', 'tenant_id', 'department_id', 'deleted_at', 'created_at'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $plan = [];

        foreach ($rows->groupBy(fn ($row): string => $row->tenant_id.':'.$row->department_id) as $group) {
            if ($group->count() < 2) {
                continue;
            }

            /** @var object{id: int, tenant_id: int, department_id: int} $keeper */
            $keeper = $group->sortBy('id')->first();

            $duplicates = $group
                ->reject(fn ($row): bool => (int) $row->id === (int) $keeper->id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();

            $plan[] = [
                'tenant_id' => (int) $keeper->tenant_id,
                'department_id' => (int) $keeper->department_id,
                'keeper_id' => (int) $keeper->id,
                'duplicate_ids' => $duplicates,
            ];
        }

        return $plan;
    }

    /**
     * @param  list<array<string, mixed>>  $plan
     * @return array{retired: int, remapped: array<string, int>, merged: array<string, int>}
     */
    public function execute(array $plan): array
    {
        $summary = ['retired' => 0, 'remapped' => [], 'merged' => []];

        DB::transaction(function () use ($plan, &$summary): void {
            foreach ($plan as $entry) {
                foreach ($entry['duplicate_ids'] as $duplicateId) {
                    foreach (self::REMAP_TARGETS as $target) {
                        $counts = $this->remap($target, (int) $duplicateId, (int) $entry['keeper_id']);

                        if ($counts['merged'] > 0) {
                            $summary['merged'][$target['table']] = ($summary['merged'][$target['table']] ?? 0) + $counts['merged'];
                        }

                        if ($counts['remapped'] > 0) {
                            $summary['remapped'][$target['table']] = ($summary['remapped'][$target['table']] ?? 0) + $counts['remapped'];
                        }
                    }

                    DB::table('institution_departments')
                        ->where('id', $duplicateId)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now(), 'updated_at' => now()]);

                    $summary['retired']++;
                }
            }
        });

        return $summary;
    }

    /**
     * @param  array{table: string, unique: list<string>}  $target
     * @return array{remapped: int, merged: int}
     */
    private function remap(array $target, int $from, int $to): array
    {
        $table = $target['table'];
        $column = 'institution_department_id';

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return ['remapped' => 0, 'merged' => 0];
        }

        $uniqueColumns = array_values(array_filter(
            $target['unique'],
            fn (string $unique): bool => Schema::hasColumn($table, $unique),
        ));

        $merged = in_array($column, $uniqueColumns, true)
            ? $this->dropCollisions($table, $uniqueColumns, $column, $from, $to)
            : 0;

        $remapped = DB::table($table)->where($column, $from)->update([$column => $to]);

        return ['remapped' => $remapped, 'merged' => $merged];
    }

    /**
     * @param  list<string>  $uniqueColumns
     */
    private function dropCollisions(string $table, array $uniqueColumns, string $column, int $from, int $to): int
    {
        $merged = 0;

        $rows = DB::table($table)
            ->where($column, $from)
            ->get(['id', ...array_diff($uniqueColumns, ['id'])]);

        foreach ($rows as $row) {
            $existing = DB::table($table)->where('id', '<>', $row->id);

            foreach ($uniqueColumns as $uniqueColumn) {
                $value = $uniqueColumn === $column ? $to : $row->{$uniqueColumn};
                $value === null
                    ? $existing->whereNull($uniqueColumn)
                    : $existing->where($uniqueColumn, $value);
            }

            if (! $existing->exists()) {
                continue;
            }

            DB::table($table)->where('id', $row->id)->delete();
            $merged++;
        }

        return $merged;
    }
}
