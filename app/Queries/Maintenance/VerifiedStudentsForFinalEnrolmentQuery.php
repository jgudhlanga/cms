<?php

declare(strict_types=1);

namespace App\Queries\Maintenance;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Students\StudentApplication;
use Illuminate\Database\Eloquent\Builder;

class VerifiedStudentsForFinalEnrolmentQuery
{
    /**
     * @return Builder<StudentApplication>
     */
    public function baseQuery(): Builder
    {
        return StudentApplication::query()
            ->select('student_applications.*')
            ->selectSub(
                ClassList::query()
                    ->selectRaw('MIN(id)')
                    ->whereColumn('class_lists.student_application_id', 'student_applications.id')
                    ->where('class_lists.type', ClassListTypeEnum::VERIFIED->value),
                'classListId',
            )
            ->whereHas(
                'classList',
                fn (Builder $query) => $query->where('type', ClassListTypeEnum::VERIFIED->value),
            )
            ->whereHas(
                'departmentWorkflowStep.workflowStep',
                fn (Builder $query) => $query->where('name', WorkflowStepEnum::ACCEPTED->name()),
            );
    }

    /**
     * @return Builder<StudentApplication>
     */
    public function withRelations(): Builder
    {
        return $this->baseQuery()->with([
            'student.user',
            'institutionDepartment.department',
            'departmentCourse.course',
            'departmentLevel.level',
        ]);
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     * }  $filters
     * @return Builder<StudentApplication>
     */
    public function applyFilters(Builder $query, array $filters = []): Builder
    {
        $query = $this->applySearch($query, $filters);

        $departmentIds = $this->intListFromFilter($filters['department'] ?? null);
        if ($departmentIds !== []) {
            $query->whereIn('student_applications.institution_department_id', $departmentIds);
        }

        $levelIds = $this->intListFromFilter($filters['level'] ?? null);
        if ($levelIds !== []) {
            $query->whereHas(
                'departmentLevel',
                fn (Builder $levelQuery) => $levelQuery->whereIn('level_id', $levelIds),
            );
        }

        $courseIds = $this->intListFromFilter($filters['course'] ?? null);
        if ($courseIds !== []) {
            $query->whereIn('student_applications.department_course_id', $courseIds);
        }

        return $query;
    }

    /**
     * @param  array{search?: string|null}  $filters
     * @return Builder<StudentApplication>
     */
    public function applySearch(Builder $query, array $filters = []): Builder
    {
        $search = $filters['search'] ?? null;

        if (! is_string($search) || $search === '') {
            return $query;
        }

        $term = '%'.addcslashes($search, '\%_').'%';

        return $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->whereHas('student', fn (Builder $student) => $student
                    ->where('student_number', 'like', $term)
                    ->orWhere('id_number', 'like', $term)
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term),
                    ),
                )
                ->orWhereHas('institutionDepartment.department', fn (Builder $department) => $department
                    ->where('name', 'like', $term),
                )
                ->orWhereHas('departmentCourse.course', fn (Builder $course) => $course
                    ->where('name', 'like', $term),
                )
                ->orWhereHas('departmentLevel.level', fn (Builder $level) => $level
                    ->where('name', 'like', $term),
                );
        });
    }

    /**
     * @return list<int>
     */
    private function intListFromFilter(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $values = is_array($value) ? $value : [$value];
        $ids = [];

        foreach ($values as $item) {
            $id = (int) $item;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
