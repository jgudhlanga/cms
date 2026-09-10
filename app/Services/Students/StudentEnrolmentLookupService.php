<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Helpers\Helper;
use App\Models\Students\Student;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudentEnrolmentLookupService
{
    private const int SEARCH_LIMIT = 20;

    private const int SUGGESTION_LIMIT = 5;

    /**
     * Search enrolled students — rows backed by `student_enrolments` — for the profile lookup drawer.
     *
     * @param  array{
     *     institution_department_id?: int|null,
     *     department_level_id?: int|null,
     *     department_course_id?: int|null,
     *     name?: string|null,
     *     search?: string|null
     * }  $filters
     * @return Collection<int, Student>
     */
    public function search(array $filters): Collection
    {
        $departmentIds = $this->resolveDepartmentIds($filters['institution_department_id'] ?? null);

        if ($departmentIds === null) {
            return collect();
        }

        $levelId = $this->intOrNull($filters['department_level_id'] ?? null);
        $courseId = $this->intOrNull($filters['department_course_id'] ?? null);

        // Filter through whereHas rather than a join so a student with several enrolments is
        // listed once, and so department/level/course all have to match the *same* enrolment.
        $query = Student::query()
            ->join('users', 'users.id', '=', 'students.user_id')
            ->with([
                'user:id,first_name,middle_name,last_name',
                'latestEnrolment.institutionDepartment.department',
                'latestEnrolment.departmentLevel.level',
                'latestEnrolment.departmentCourse.course',
                'latestEnrolment.modeOfStudy',
            ])
            ->whereHas('enrolments', function (Builder $enrolments) use ($departmentIds, $levelId, $courseId): void {
                if ($departmentIds !== []) {
                    $enrolments->whereIn('institution_department_id', $departmentIds);
                }

                if ($levelId !== null) {
                    $enrolments->where('department_level_id', $levelId);
                }

                if ($courseId !== null) {
                    $enrolments->where('department_course_id', $courseId);
                }
            })
            ->select('students.*')
            ->orderBy('users.last_name')
            ->orderBy('users.first_name');

        $name = trim((string) ($filters['name'] ?? ''));
        $studentDetails = trim((string) ($filters['search'] ?? ''));

        if ($name === '' && $studentDetails === '') {
            if ($courseId === null) {
                return collect();
            }

            return $query->limit(self::SUGGESTION_LIMIT)->get();
        }

        if ($name !== '') {
            if (mb_strlen($name) < 2) {
                return collect();
            }

            $this->applyNameFilter($query, $name);
        }

        if ($studentDetails !== '') {
            if (mb_strlen($studentDetails) < 2) {
                return collect();
            }

            $this->applyStudentDetailsFilter($query, $studentDetails);
        }

        return $query->limit(self::SEARCH_LIMIT)->get();
    }

    /**
     * Resolve which departments the search may span.
     *
     * @return list<int>|null Empty list means "all departments"; null means the user may see none.
     */
    private function resolveDepartmentIds(mixed $requested): ?array
    {
        $departmentIds = ($id = $this->intOrNull($requested)) !== null ? [$id] : [];

        if (! Helper::isDepartmentUser()) {
            return $departmentIds;
        }

        $userDepartments = Helper::resolveUserDepartments();

        if ($userDepartments === []) {
            return null;
        }

        $departmentIds = $departmentIds !== []
            ? array_values(array_intersect($departmentIds, $userDepartments))
            : array_values($userDepartments);

        return $departmentIds === [] ? null : $departmentIds;
    }

    /**
     * @param  Builder<Student>  $query
     */
    private function applyNameFilter(Builder $query, string $name): void
    {
        $query->whereHas('user', function (Builder $userQuery) use ($name): void {
            $userQuery->whereRaw(
                "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                ['%'.$name.'%'],
            );
        });
    }

    /**
     * @param  Builder<Student>  $query
     */
    private function applyStudentDetailsFilter(Builder $query, string $term): void
    {
        $compactId = strtoupper(str_replace('-', '', $term));

        $query->where(function (QueryBuilderContract $builder) use ($term, $compactId): void {
            $builder->where('students.student_number', 'like', '%'.$term.'%')
                ->orWhere('students.id_number', 'like', '%'.$term.'%')
                ->orWhereRaw("UPPER(REPLACE(students.id_number, '-', '')) LIKE ?", ['%'.$compactId.'%'])
                ->orWhere('students.passport_number', 'like', '%'.$term.'%');
        });
    }

    private function intOrNull(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }
}
