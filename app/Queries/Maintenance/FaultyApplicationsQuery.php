<?php

declare(strict_types=1);

namespace App\Queries\Maintenance;

use App\Models\Students\StudentApplication;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applications that cannot be processed because part of the programme
 * selection is missing.
 */
class FaultyApplicationsQuery
{
    public function baseQuery(): Builder
    {
        return $this->applyProgrammeGapConstraint(StudentApplication::query())
            ->with([
                'student.user',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'modeOfStudy',
                'intakePeriod',
                'workflowStep',
            ])
            ->orderBy('student_applications.id');
    }

    public function count(): int
    {
        return $this->applyProgrammeGapConstraint(StudentApplication::query())->count();
    }

    public function applySearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || trim($search) === '') {
            return $query;
        }

        $term = '%'.trim($search).'%';

        return $query->where(function (Builder $builder) use ($term): void {
            $builder->where('student_applications.application_tracking_number', 'like', $term)
                ->orWhereHas('student', function (Builder $student) use ($term): void {
                    $student->where('student_number', 'like', $term)
                        ->orWhere('id_number', 'like', $term)
                        ->orWhereHas('user', function (Builder $user) use ($term): void {
                            $user->where('first_name', 'like', $term)
                                ->orWhere('middle_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
        });
    }

    private function applyProgrammeGapConstraint(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->whereNull('student_applications.department_level_id')
                ->orWhereDoesntHave('departmentLevel')
                ->orWhereDoesntHave('departmentLevel.level')
                ->orWhereHas('departmentLevel.level', fn (Builder $level) => $level->whereRaw("TRIM(COALESCE(levels.name, '')) = ''"))
                ->orWhereNull('student_applications.institution_department_id')
                ->orWhereDoesntHave('institutionDepartment')
                ->orWhereNull('student_applications.department_course_id')
                ->orWhereDoesntHave('departmentCourse')
                ->orWhereNull('student_applications.mode_of_study_id')
                ->orWhereDoesntHave('modeOfStudy')
                ->orWhereNull('student_applications.intake_period_id')
                ->orWhereDoesntHave('intakePeriod');
        });
    }
}
