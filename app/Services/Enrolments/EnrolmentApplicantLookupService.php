<?php

declare(strict_types=1);

namespace App\Services\Enrolments;

use App\Models\Students\StudentApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EnrolmentApplicantLookupService
{
    private const int SEARCH_LIMIT = 20;

    private const int SUGGESTION_LIMIT = 5;

    /**
     * @param  array{
     *     type: string,
     *     intake_period_id: int,
     *     institution_department_id?: int|null,
     *     department_level_id?: int|null,
     *     department_course_id?: int|null,
     *     q?: string|null
     * }  $filters
     * @return Collection<int, StudentApplication>
     */
    public function search(array $filters): Collection
    {
        $query = StudentApplication::query()
            ->join('class_lists', 'class_lists.student_application_id', '=', 'student_applications.id')
            ->join('students', 'students.id', '=', 'student_applications.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->with([
                'student.user:id,first_name,middle_name,last_name',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
            ])
            ->where('class_lists.type', $filters['type'])
            ->where('student_applications.intake_period_id', $filters['intake_period_id'])
            ->when(
                filled($filters['institution_department_id'] ?? null),
                fn (Builder $builder) => $builder->where(
                    'student_applications.institution_department_id',
                    (int) $filters['institution_department_id'],
                ),
            )
            ->when(
                filled($filters['department_level_id'] ?? null),
                fn (Builder $builder) => $builder->where(
                    'student_applications.department_level_id',
                    (int) $filters['department_level_id'],
                ),
            )
            ->when(
                filled($filters['department_course_id'] ?? null),
                fn (Builder $builder) => $builder->where(
                    'student_applications.department_course_id',
                    (int) $filters['department_course_id'],
                ),
            )
            ->select([
                'student_applications.*',
            ])
            ->orderBy('users.last_name')
            ->orderBy('users.first_name');

        $term = trim((string) ($filters['q'] ?? ''));

        if ($term === '') {
            if (! filled($filters['department_course_id'] ?? null)) {
                return collect();
            }

            return $query->limit(self::SUGGESTION_LIMIT)->get();
        }

        if (mb_strlen($term) < 2) {
            return collect();
        }

        $compactId = strtoupper(str_replace('-', '', $term));

        $query->where(function (Builder $builder) use ($term, $compactId): void {
            $builder->where('student_applications.application_tracking_number', 'like', '%'.$term.'%')
                ->orWhere(DB::raw('UPPER(REPLACE(students.id_number, "-", ""))'), 'like', '%'.$compactId.'%')
                ->orWhere('students.id_number', 'like', '%'.$term.'%')
                ->orWhere('students.passport_number', 'like', '%'.$term.'%')
                ->orWhereRaw(
                    "CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.middle_name, ''), ' ', COALESCE(users.last_name, '')) LIKE ?",
                    ['%'.$term.'%'],
                );
        });

        return $query->limit(self::SEARCH_LIMIT)->get();
    }
}
