<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Institution;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-links applications that were orphaned when Link Levels / Link Courses
 * soft-deleted a department level or course and minted a replacement id.
 *
 * The oldest surviving id is not automatically right: the canonical row is the
 * one the most live applications already point at, so the fewest foreign keys
 * have to move.
 */
class DepartmentProgrammeLinkRepairService
{
    /**
     * Tables holding a department_level_id, with the unique index that a remap
     * can collide with. An empty list means the update can never collide.
     *
     * @var list<array{table: string, unique: list<string>}>
     */
    private const LEVEL_TARGETS = [
        ['table' => 'student_applications', 'unique' => []],
        ['table' => 'student_enrolments', 'unique' => []],
        ['table' => 'student_exam_results', 'unique' => []],
        ['table' => 'department_level_courses', 'unique' => ['department_course_id', 'department_level_id']],
        ['table' => 'application_offering_levels', 'unique' => ['application_offering_department_id', 'department_level_id']],
        ['table' => 'application_level_requirements', 'unique' => ['tenant_id', 'department_level_id']],
        ['table' => 'application_course_requirements', 'unique' => ['tenant_id', 'department_level_id', 'department_course_id']],
        ['table' => 'course_level_modes', 'unique' => ['department_course_id', 'department_level_id']],
        ['table' => 'class_configs', 'unique' => [
            'institution_department_id', 'department_course_id', 'department_level_id',
            'mode_of_study_id', 'calendar_year', 'semester_id',
        ]],
        ['table' => 'department_intake_class_sizes', 'unique' => []],
    ];

    /**
     * @var list<array{table: string, unique: list<string>}>
     */
    private const COURSE_TARGETS = [
        ['table' => 'student_applications', 'unique' => []],
        ['table' => 'student_enrolments', 'unique' => []],
        ['table' => 'student_exam_results', 'unique' => []],
        ['table' => 'department_level_courses', 'unique' => ['department_course_id', 'department_level_id']],
        ['table' => 'application_offering_courses', 'unique' => ['application_offering_level_id', 'department_course_id']],
        ['table' => 'application_course_requirements', 'unique' => ['tenant_id', 'department_level_id', 'department_course_id']],
        ['table' => 'course_level_modes', 'unique' => ['department_course_id', 'department_level_id']],
        ['table' => 'class_configs', 'unique' => [
            'institution_department_id', 'department_course_id', 'department_level_id',
            'mode_of_study_id', 'calendar_year', 'semester_id',
        ]],
        ['table' => 'department_intake_class_sizes', 'unique' => []],
    ];

    /**
     * @return list<array{
     *     kind: string,
     *     department_id: int,
     *     catalog_id: int,
     *     canonical_id: int,
     *     canonical_was_trashed: bool,
     *     duplicates: list<int>,
     *     applications: int,
     * }>
     */
    public function plan(): array
    {
        return [
            ...$this->planFor('level'),
            ...$this->planFor('course'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $plan
     * @return array{restored: int, remapped: array<string, int>, merged: array<string, int>, trashed: int}
     */
    public function execute(array $plan): array
    {
        $summary = ['restored' => 0, 'remapped' => [], 'merged' => [], 'trashed' => 0];

        DB::transaction(function () use ($plan, &$summary): void {
            foreach ($plan as $entry) {
                $isLevel = $entry['kind'] === 'level';
                $table = $isLevel ? 'department_levels' : 'department_courses';
                $column = $isLevel ? 'department_level_id' : 'department_course_id';
                $targets = $isLevel ? self::LEVEL_TARGETS : self::COURSE_TARGETS;

                if ($entry['canonical_was_trashed']) {
                    DB::table($table)->where('id', $entry['canonical_id'])->update([
                        'deleted_at' => null,
                        'updated_at' => now(),
                    ]);
                    $summary['restored']++;
                }

                foreach ($entry['duplicates'] as $duplicateId) {
                    foreach ($targets as $target) {
                        $counts = $this->remap($target, $column, (int) $duplicateId, (int) $entry['canonical_id']);

                        if ($counts['merged'] > 0) {
                            $summary['merged'][$target['table']] = ($summary['merged'][$target['table']] ?? 0) + $counts['merged'];
                        }

                        if ($counts['remapped'] > 0) {
                            $summary['remapped'][$target['table']] = ($summary['remapped'][$target['table']] ?? 0) + $counts['remapped'];
                        }
                    }

                    DB::table($table)
                        ->where('id', $duplicateId)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now(), 'updated_at' => now()]);

                    $summary['trashed']++;
                }
            }
        });

        return $summary;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function planFor(string $kind): array
    {
        $isLevel = $kind === 'level';
        $table = $isLevel ? 'department_levels' : 'department_courses';
        $catalogColumn = $isLevel ? 'level_id' : 'course_id';
        $usageColumn = $isLevel ? 'department_level_id' : 'department_course_id';

        $rows = DB::table($table)
            ->select(['id', 'institution_department_id', $catalogColumn, 'deleted_at', 'created_at'])
            ->orderBy('id')
            ->get();

        $usage = $this->usageCounts($usageColumn);

        $plan = [];

        foreach ($rows->groupBy(fn ($row): string => $row->institution_department_id.':'.$row->{$catalogColumn}) as $group) {
            $entry = $this->planEntry($kind, $group, $catalogColumn, $usage);

            if ($entry !== null) {
                $plan[] = $entry;
            }
        }

        return $plan;
    }

    /**
     * @param  Collection<int, object>  $group
     * @param  array<int, int>  $usage
     * @return array<string, mixed>|null
     */
    private function planEntry(string $kind, Collection $group, string $catalogColumn, array $usage): ?array
    {
        $referenced = $group->filter(fn ($row): bool => ($usage[(int) $row->id] ?? 0) > 0);
        $live = $group->filter(fn ($row): bool => $row->deleted_at === null);

        // A single row that is already live needs nothing; a single trashed row
        // that applications still point at needs restoring.
        if ($group->count() === 1) {
            $row = $group->first();

            if ($row->deleted_at === null || $referenced->isEmpty()) {
                return null;
            }

            return $this->entry($kind, $row, $catalogColumn, [], $usage);
        }

        $canonical = $this->pickCanonical($group, $usage);
        $duplicates = $group
            ->reject(fn ($row): bool => (int) $row->id === (int) $canonical->id)
            ->values();

        // Nothing is orphaned when only the canonical row is live and the extras
        // are already trashed and unreferenced.
        if ($canonical->deleted_at === null
            && $live->count() === 1
            && $referenced->every(fn ($row): bool => (int) $row->id === (int) $canonical->id)) {
            return null;
        }

        return $this->entry($kind, $canonical, $catalogColumn, $duplicates->pluck('id')->map('intval')->all(), $usage);
    }

    /**
     * @param  Collection<int, object>  $group
     * @param  array<int, int>  $usage
     */
    private function pickCanonical(Collection $group, array $usage): object
    {
        return $group
            ->sort(fn ($first, $second): int => [
                -($usage[(int) $first->id] ?? 0), (string) $first->created_at, (int) $first->id,
            ] <=> [
                -($usage[(int) $second->id] ?? 0), (string) $second->created_at, (int) $second->id,
            ])
            ->first();
    }

    /**
     * @param  list<int>  $duplicates
     * @param  array<int, int>  $usage
     * @return array<string, mixed>
     */
    private function entry(string $kind, object $canonical, string $catalogColumn, array $duplicates, array $usage): array
    {
        $applications = array_sum(array_map(
            fn (int $id): int => $usage[$id] ?? 0,
            [(int) $canonical->id, ...$duplicates],
        ));

        return [
            'kind' => $kind,
            'department_id' => (int) $canonical->institution_department_id,
            'catalog_id' => (int) $canonical->{$catalogColumn},
            'canonical_id' => (int) $canonical->id,
            'canonical_was_trashed' => $canonical->deleted_at !== null,
            'duplicates' => $duplicates,
            'applications' => $applications,
        ];
    }

    /**
     * Live applications per link id, used to choose the canonical row.
     *
     * @return array<int, int>
     */
    private function usageCounts(string $column): array
    {
        return DB::table('student_applications')
            ->whereNull('deleted_at')
            ->whereNotNull($column)
            ->selectRaw("{$column} as link_id, COUNT(*) as aggregate")
            ->groupBy($column)
            ->pluck('aggregate', 'link_id')
            ->mapWithKeys(fn ($aggregate, $linkId): array => [(int) $linkId => (int) $aggregate])
            ->all();
    }

    /**
     * @param  array{table: string, unique: list<string>}  $target
     * @return array{remapped: int, merged: int}
     */
    private function remap(array $target, string $column, int $from, int $to): array
    {
        $table = $target['table'];

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
     * Removes rows whose remapped unique key already exists on the canonical id,
     * so the remap update itself can never violate the index.
     *
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
