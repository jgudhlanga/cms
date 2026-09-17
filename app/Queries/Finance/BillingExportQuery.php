<?php

declare(strict_types=1);

namespace App\Queries\Finance;

use App\Enums\Finance\StudentBillingStatusEnum;
use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Finance\StudentBillingRecord;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use App\Support\Finance\BillingExportFilters;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BillingExportQuery
{
    public function baseQuery(BillingExportFilters $filters): Builder
    {
        $periodIds = $filters->academicCalendarIds === [] ? [0] : $filters->academicCalendarIds;

        return StudentStudyPositionConfirmation::query()
            ->whereIn('academic_calendar_id', $periodIds)
            ->where('answer', StudyPositionAnswerEnum::PHASE->value)
            ->where('sync_status', '!=', StudyPositionSyncStatusEnum::NEEDS_REVIEW->value)
            ->whereNotNull('programme_semester_id')
            ->when($filters->programmeSemesterIds !== [], function (Builder $query) use ($filters): void {
                $query->whereIn('programme_semester_id', $filters->programmeSemesterIds);
            })
            ->when($filters->sources !== [], function (Builder $query) use ($filters): void {
                $query->whereIn('source', $filters->sources);
            })
            ->when($filters->syncStatuses !== [], function (Builder $query) use ($filters): void {
                $query->whereIn('sync_status', $filters->syncStatuses);
            })
            ->when($filters->confirmedFrom !== null, function (Builder $query) use ($filters): void {
                $query->whereDate('confirmed_at', '>=', $filters->confirmedFrom);
            })
            ->when($filters->confirmedTo !== null, function (Builder $query) use ($filters): void {
                $query->whereDate('confirmed_at', '<=', $filters->confirmedTo);
            })
            ->whereHas('enrolment', function (Builder $query) use ($filters): void {
                $query
                    ->whereNull('student_enrolments.deleted_at')
                    ->when($filters->institutionDepartmentId !== null, function (Builder $enrolmentQuery) use ($filters): void {
                        $enrolmentQuery->where('institution_department_id', $filters->institutionDepartmentId);
                    })
                    ->when($filters->departmentLevelId !== null, function (Builder $enrolmentQuery) use ($filters): void {
                        $enrolmentQuery->where('department_level_id', $filters->departmentLevelId);
                    })
                    ->when($filters->departmentCourseId !== null, function (Builder $enrolmentQuery) use ($filters): void {
                        $enrolmentQuery->where('department_course_id', $filters->departmentCourseId);
                    })
                    ->when($filters->modeOfStudyId !== null, function (Builder $enrolmentQuery) use ($filters): void {
                        $enrolmentQuery->where('mode_of_study_id', $filters->modeOfStudyId);
                    })
                    ->when($filters->studentNumberStartsWith !== null, function (Builder $enrolmentQuery) use ($filters): void {
                        $enrolmentQuery->whereHas('student', function (Builder $studentQuery) use ($filters): void {
                            $studentQuery
                                ->where('student_number', 'like', $filters->studentNumberStartsWith.'%')
                                ->whereNull('students.deleted_at');
                        });
                    });
            })
            ->when($filters->pastelLinked === 'linked', function (Builder $query): void {
                $query->whereExists(function ($sub): void {
                    $sub->select(DB::raw(1))
                        ->from('pastel_linked_students')
                        ->whereColumn('pastel_linked_students.student_id', 'student_study_position_confirmations.student_id');
                });
            })
            ->when($filters->pastelLinked === 'unlinked', function (Builder $query): void {
                $query->whereNotExists(function ($sub): void {
                    $sub->select(DB::raw(1))
                        ->from('pastel_linked_students')
                        ->whereColumn('pastel_linked_students.student_id', 'student_study_position_confirmations.student_id');
                });
            })
            ->whereNotExists(function ($query): void {
                $query
                    ->select(DB::raw(1))
                    ->from('student_billing_records')
                    ->whereColumn('student_billing_records.student_enrolment_id', 'student_study_position_confirmations.student_enrolment_id')
                    ->whereColumn('student_billing_records.academic_calendar_id', 'student_study_position_confirmations.academic_calendar_id')
                    ->whereColumn('student_billing_records.programme_semester_id', 'student_study_position_confirmations.programme_semester_id')
                    ->where('student_billing_records.status', StudentBillingStatusEnum::BILLED->value);
            })
            ->with([
                'enrolment.student.user',
                'enrolment.student.contacts',
                'enrolment.student.activeHostelAllocation',
                'enrolment.studentApplication.modeOfStudy',
                'enrolment.institutionDepartment.department',
                'enrolment.departmentLevel.level',
                'enrolment.departmentCourse.course',
                'enrolment.modeOfStudy',
                'programmeSemester',
                'academicCalendar',
            ])
            ->orderBy('id');
    }

    public function count(BillingExportFilters $filters): int
    {
        return $this->baseQuery($filters)->count();
    }

    /**
     * @return array{total: int, billed_today: int, ready_to_bill: int|null}
     */
    public function stats(BillingExportFilters $filters): array
    {
        $total = StudentBillingRecord::query()
            ->where('status', StudentBillingStatusEnum::BILLED->value)
            ->count();

        $billedToday = StudentBillingRecord::query()
            ->where('status', StudentBillingStatusEnum::BILLED->value)
            ->whereDate('billed_at', now()->toDateString())
            ->count();

        return [
            'total' => $total,
            'billed_today' => $billedToday,
            'ready_to_bill' => $filters->hasPeriods() ? $this->count($filters) : null,
        ];
    }

    public function recordsQuery(?string $search = null, array $periodIds = []): Builder
    {
        return StudentBillingRecord::query()
            ->with([
                'student.user',
                'enrolment.institutionDepartment.department',
                'enrolment.departmentLevel.level',
                'enrolment.departmentCourse.course',
                'programmeSemester',
                'academicCalendar',
                'billedBy',
                'batch',
            ])
            ->withExists('pastelLink')
            ->when($periodIds !== [], function (Builder $query) use ($periodIds): void {
                $query->whereIn('academic_calendar_id', $periodIds);
            })
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
            ->latest('exported_at');
    }

    /**
     * @return array{
     *     phases: list<array{value: int, label: string}>,
     *     sources: list<array{value: string, label: string}>,
     *     syncStatuses: list<array{value: string, label: string}>,
     *     departments: list<array{value: int, label: string}>,
     *     levels: list<array{value: int, label: string}>,
     *     courses: list<array{value: int, label: string}>,
     *     modesOfStudy: list<array{value: int, label: string}>,
     *     pastelLinked: list<array{value: string, label: string}>
     * }
     */
    public function filterOptions(): array
    {
        $confirmationQuery = StudentStudyPositionConfirmation::query()
            ->where('answer', StudyPositionAnswerEnum::PHASE->value)
            ->whereNotNull('programme_semester_id');

        $phaseIds = (clone $confirmationQuery)->distinct()->pluck('programme_semester_id');
        $enrolmentIds = (clone $confirmationQuery)->distinct()->pluck('student_enrolment_id');

        $enrolments = DB::table('student_enrolments')
            ->whereIn('id', $enrolmentIds)
            ->whereNull('deleted_at')
            ->get([
                'institution_department_id',
                'department_level_id',
                'department_course_id',
                'mode_of_study_id',
            ]);

        $phases = ProgrammeSemester::query()
            ->whereIn('id', $phaseIds)
            ->with('departmentLevelCourse.departmentLevel.level')
            ->orderBy('name')
            ->get()
            ->map(function (ProgrammeSemester $phase): array {
                $levelName = $phase->departmentLevelCourse?->departmentLevel?->level?->name;

                return [
                    'value' => (int) $phase->id,
                    'label' => ProgrammeSemesterNameFormatter::qualifiedName($levelName, $phase->name),
                ];
            })
            ->values()
            ->all();

        $departments = InstitutionDepartment::query()
            ->whereIn('id', $enrolments->pluck('institution_department_id')->filter()->unique()->all())
            ->with('department')
            ->get()
            ->map(fn (InstitutionDepartment $department): array => [
                'value' => (int) $department->id,
                'label' => (string) ($department->department?->name ?? $department->department_code),
            ])
            ->sortBy('label')
            ->values()
            ->all();

        $levels = DepartmentLevel::query()
            ->whereIn('id', $enrolments->pluck('department_level_id')->filter()->unique()->all())
            ->with('level')
            ->get()
            ->map(fn (DepartmentLevel $departmentLevel): array => [
                'value' => (int) $departmentLevel->id,
                'label' => (string) ($departmentLevel->level?->name ?? $departmentLevel->id),
            ])
            ->sortBy('label')
            ->values()
            ->all();

        $courses = DepartmentCourse::query()
            ->whereIn('id', $enrolments->pluck('department_course_id')->filter()->unique()->all())
            ->with('course')
            ->get()
            ->map(fn (DepartmentCourse $departmentCourse): array => [
                'value' => (int) $departmentCourse->id,
                'label' => (string) ($departmentCourse->course?->name ?? $departmentCourse->id),
            ])
            ->sortBy('label')
            ->values()
            ->all();

        $modesOfStudy = ModeOfStudy::query()
            ->whereIn('id', $enrolments->pluck('mode_of_study_id')->filter()->unique()->all())
            ->orderBy('name')
            ->get()
            ->map(fn (ModeOfStudy $mode): array => [
                'value' => (int) $mode->id,
                'label' => (string) $mode->name,
            ])
            ->values()
            ->all();

        return [
            'phases' => $phases,
            'sources' => array_map(
                static fn (StudyPositionSourceEnum $source): array => [
                    'value' => $source->value,
                    'label' => $source->label(),
                ],
                StudyPositionSourceEnum::cases(),
            ),
            'syncStatuses' => [
                [
                    'value' => StudyPositionSyncStatusEnum::APPLIED->value,
                    'label' => __('finance.billing_export_sync_applied'),
                ],
                [
                    'value' => StudyPositionSyncStatusEnum::UNCHANGED->value,
                    'label' => __('finance.billing_export_sync_unchanged'),
                ],
            ],
            'departments' => $departments,
            'levels' => $levels,
            'courses' => $courses,
            'modesOfStudy' => $modesOfStudy,
            'pastelLinked' => [
                ['value' => 'all', 'label' => __('finance.billing_export_pastel_all')],
                ['value' => 'linked', 'label' => __('finance.billing_export_pastel_linked')],
                ['value' => 'unlinked', 'label' => __('finance.billing_export_pastel_unlinked')],
            ],
        ];
    }

    /**
     * @return list<array{id: int, label: string, calendarYear: string|null}>
     */
    public function periodOptions(): array
    {
        return AcademicCalendar::query()
            ->orderByDesc('calendar_year')
            ->orderBy('opening_date')
            ->orderBy('id')
            ->get()
            ->map(fn (AcademicCalendar $period): array => [
                'id' => (int) $period->id,
                'label' => AcademicCalendarPeriodResolver::dashboardSelectLabel($period),
                'calendarYear' => $period->calendar_year,
            ])
            ->values()
            ->all();
    }
}
