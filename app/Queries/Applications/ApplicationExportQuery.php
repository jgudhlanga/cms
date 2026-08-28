<?php

declare(strict_types=1);

namespace App\Queries\Applications;

use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Students\StudentApplication;
use App\Support\Maintenance\StudentAttributeFilterApplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ApplicationExportQuery
{
    public function __construct(
        protected StudentAttributeFilterApplier $studentFilters = new StudentAttributeFilterApplier,
    ) {}

    /**
     * @param  array<string, mixed>|string|null  $filters  Legacy callers may still pass an intake year string.
     */
    public function baseQuery(array|string|null $filters = null): Builder
    {
        return $this->rankedQuery($this->resolveFilters($filters))
            ->orderBy('student_applications.student_id')
            ->with([
                'student.user',
                'student.gender',
                'student.country',
                'student.addresses',
                'student.contacts',
                'departmentLevel',
                'departmentCourse',
                'intakePeriod',
                'modeOfStudy',
                'workflowStep',
            ]);
    }

    /**
     * @param  array<string, mixed>|string|null  $filters
     */
    public function count(array|string|null $filters = null): int
    {
        return $this->rankedQuery($this->resolveFilters($filters))->count();
    }

    /**
     * Lightweight paginated rows used by the export preview screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function preview(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->rankedQuery($filters)
            ->with([
                'student.user',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'intakePeriod',
                'modeOfStudy',
                'workflowStep',
            ])
            ->orderBy('student_applications.student_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: int, byWorkflowStep: list<array{name: string, count: int}>, byLevel: list<array{name: string, count: int}>}
     */
    public function stats(array $filters): array
    {
        return [
            'total' => $this->rankedQuery($filters)->count(),
            'byWorkflowStep' => $this->breakdown($filters, function ($query): string {
                $query->leftJoin('workflow_steps', 'workflow_steps.id', '=', 'student_applications.workflow_step_id');

                return 'workflow_steps.name';
            }),
            'byLevel' => $this->breakdown($filters, function ($query): string {
                $query->leftJoin('department_levels', 'department_levels.id', '=', 'student_applications.department_level_id')
                    ->leftJoin('levels', 'levels.id', '=', 'department_levels.level_id');

                return 'levels.name';
            }),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function rankedQuery(array $filters): Builder
    {
        $rankedPrograms = $this->matchingProgramsQuery($filters)
            ->select([
                'student_applications.id',
                'student_applications.student_id',
            ])
            ->selectRaw($this->exportRankSelectSql(), [
                WorkflowStepEnum::ENROLLED->slug(),
            ]);

        return StudentApplication::query()
            ->whereIn('student_applications.id', function ($query) use ($rankedPrograms): void {
                $query->fromSub($rankedPrograms, 'ranked_programs')
                    ->where('ranked_programs.export_rank', 1)
                    ->select('ranked_programs.id');
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function matchingProgramsQuery(array $filters): Builder
    {
        $query = StudentApplication::query()
            ->leftJoin(
                'workflow_steps',
                'student_applications.workflow_step_id',
                '=',
                'workflow_steps.id',
            )
            ->whereNull('student_applications.deleted_at')
            ->where(function (Builder $builder): void {
                $builder->whereNotNull('student_applications.workflow_step_id')
                    ->orWhereExists(function ($subQuery): void {
                        $subQuery->selectRaw('1')
                            ->from('student_enrolments')
                            ->whereColumn('student_enrolments.student_application_id', 'student_applications.id')
                            ->whereNull('student_enrolments.deleted_at');
                    });
            });

        $this->applyIntakeFilters($query, $filters);
        $this->applyProgrammeFilters($query, $filters);

        if ($this->studentFilters->hasFilters($filters)) {
            $query->whereHas('student', fn (Builder $studentQuery) => $this->studentFilters->apply($studentQuery, $filters));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyIntakeFilters(Builder $query, array $filters): void
    {
        $intakeYear = $filters['intake_year'] ?? null;

        if (is_string($intakeYear) && $intakeYear !== '') {
            $query->whereHas('intakePeriod', fn (Builder $intakeQuery) => $intakeQuery
                ->where('calendar_year', $intakeYear)
                ->whereNull('intake_periods.deleted_at'));
        }

        $intakePeriodId = $filters['intake_period_id'] ?? null;

        if (is_numeric($intakePeriodId) && (int) $intakePeriodId > 0) {
            $query->where('student_applications.intake_period_id', (int) $intakePeriodId);
        }

        $appliedFrom = $filters['applied_from'] ?? null;

        if (is_string($appliedFrom) && $appliedFrom !== '') {
            $query->whereDate('student_applications.created_at', '>=', $appliedFrom);
        }

        $appliedTo = $filters['applied_to'] ?? null;

        if (is_string($appliedTo) && $appliedTo !== '') {
            $query->whereDate('student_applications.created_at', '<=', $appliedTo);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyProgrammeFilters(Builder $query, array $filters): void
    {
        if ($this->idList($filters, 'department') !== []) {
            $query->whereIn('student_applications.institution_department_id', $this->idList($filters, 'department'));
        }

        if ($this->idList($filters, 'level') !== []) {
            $levelIds = $this->idList($filters, 'level');
            $query->whereHas('departmentLevel', fn (Builder $levelQuery) => $levelQuery->whereIn('level_id', $levelIds));
        }

        if ($this->idList($filters, 'course') !== []) {
            $query->whereIn('student_applications.department_course_id', $this->idList($filters, 'course'));
        }

        if ($this->idList($filters, 'mode_of_study') !== []) {
            $query->whereIn('student_applications.mode_of_study_id', $this->idList($filters, 'mode_of_study'));
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  callable(\Illuminate\Database\Query\Builder): string  $joinLabelColumn
     * @return list<array{name: string, count: int}>
     */
    private function breakdown(array $filters, callable $joinLabelColumn): array
    {
        $query = $this->rankedQuery($filters)->toBase();
        $labelColumn = $joinLabelColumn($query);

        return $query
            ->selectRaw($labelColumn.' as label, COUNT(*) as aggregate')
            ->groupBy($labelColumn)
            ->orderByDesc('aggregate')
            ->get()
            ->map(static fn ($row): array => [
                'name' => (string) ($row->label ?: '—'),
                'count' => (int) $row->aggregate,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    private function idList(array $filters, string $key): array
    {
        $value = $filters[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $value), static fn (int $id): bool => $id > 0));
    }

    /**
     * @param  array<string, mixed>|string|null  $filters
     * @return array<string, mixed>
     */
    private function resolveFilters(array|string|null $filters): array
    {
        if (is_string($filters)) {
            return $filters === '' ? [] : ['intake_year' => $filters];
        }

        return $filters ?? [];
    }

    private function exportRankSelectSql(): string
    {
        return 'ROW_NUMBER() OVER (
            PARTITION BY student_applications.student_id
            ORDER BY
                CASE
                    WHEN workflow_steps.slug = ? THEN 0
                    WHEN EXISTS (
                        SELECT 1 FROM student_enrolments
                        WHERE student_enrolments.student_application_id = student_applications.id
                        AND student_enrolments.deleted_at IS NULL
                    ) THEN 1
                    ELSE 2
                END,
                student_applications.id
        ) as export_rank';
    }
}
