<?php

declare(strict_types=1);

namespace App\Queries\Finance;

use App\Models\Finance\PastelLinkedStudent;
use App\Models\Students\StudentEnrolment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PastelExportQuery
{
    /**
     * @param  list<int>  $workflowStepIds
     */
    public function baseQuery(int $intakePeriodId, array $workflowStepIds = [], ?string $studentNumberStartsWith = null): Builder
    {
        return StudentEnrolment::query()
            ->whereHas('studentApplication', function (Builder $query) use ($intakePeriodId, $workflowStepIds): void {
                $query
                    ->where('intake_period_id', $intakePeriodId)
                    ->whereNull('student_applications.deleted_at')
                    ->when($workflowStepIds !== [], function (Builder $applicationQuery) use ($workflowStepIds): void {
                        $applicationQuery->whereIn('workflow_step_id', $workflowStepIds);
                    });
            })
            ->when($studentNumberStartsWith !== null, function (Builder $query) use ($studentNumberStartsWith): void {
                $query->whereHas('student', function (Builder $studentQuery) use ($studentNumberStartsWith): void {
                    $studentQuery
                        ->where('student_number', 'like', $studentNumberStartsWith.'%')
                        ->whereNull('students.deleted_at');
                });
            })
            ->whereNotExists(function ($query): void {
                $query
                    ->select(DB::raw(1))
                    ->from('pastel_linked_students')
                    ->whereColumn('pastel_linked_students.student_id', 'student_enrolments.student_id');
            })
            ->whereNull('student_enrolments.deleted_at')
            ->with([
                'student.user',
                'student.contacts',
                'student.addresses',
                'student.nextOfKins',
                'student.sponsors',
                'student.apprentices',
                'student.activeHostelAllocation',
                'studentApplication.modeOfStudy',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'modeOfStudy',
            ])
            ->orderBy('student_enrolments.id');
    }

    /**
     * @param  list<int>  $workflowStepIds
     */
    public function count(int $intakePeriodId, array $workflowStepIds = [], ?string $studentNumberStartsWith = null): int
    {
        return $this->baseQuery($intakePeriodId, $workflowStepIds, $studentNumberStartsWith)->count();
    }

    /**
     * @return array{total: int, linked_today: int}
     */
    public function linkedStats(): array
    {
        $total = PastelLinkedStudent::query()->count();
        $linkedToday = PastelLinkedStudent::query()
            ->whereDate('linked_at', now()->toDateString())
            ->count();

        return [
            'total' => $total,
            'linked_today' => $linkedToday,
        ];
    }

    public function linkedStudentsQuery(?string $search = null): Builder
    {
        return PastelLinkedStudent::query()
            ->with([
                'student.user',
                'linkedBy',
                'intakePeriod',
            ])
            ->when($search !== null && trim($search) !== '', function (Builder $query) use ($search): void {
                $term = '%'.trim($search).'%';

                $query->where(function (Builder $inner) use ($term): void {
                    $inner
                        ->where('student_number', 'like', $term)
                        ->orWhereHas('student', function (Builder $studentQuery) use ($term): void {
                            $studentQuery->where('student_number', 'like', $term);
                        })
                        ->orWhereHas('student.user', function (Builder $userQuery) use ($term): void {
                            $userQuery
                                ->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term);
                        });
                });
            })
            ->latest('linked_at');
    }
}
