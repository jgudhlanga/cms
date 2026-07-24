<?php

namespace App\Services;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Helpers\Helper;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApplicationMetricsService
{
    protected bool $isDepartmentUser = false;

    protected array $userDepartments = [];

    public function __construct()
    {
        $this->isDepartmentUser = Helper::isDepartmentUser();
        $this->userDepartments = Helper::resolveUserDepartments();
    }

    /**
     * Enrolment department distribution is scoped by intake period only (not academic calendar).
     */
    public function applicationsByDepartment(): Collection
    {
        $intakePeriod = Helper::resolveIntakePeriod();

        if (! $intakePeriod || ($this->isDepartmentUser && empty($this->userDepartments))) {
            return collect();
        }

        $classSizesSub = DB::table('department_intake_class_sizes')
            ->select(
                'institution_department_id',
                DB::raw('SUM(class_size) as total_class_size')
            )
            ->where('intake_period_id', $intakePeriod->id)
            ->groupBy('institution_department_id');

        $query = DB::table('student_applications')
            ->select(
                'institution_departments.id as institution_department_id',
                'departments.id as department_id',
                'departments.name as department_name',
                DB::raw('COUNT(DISTINCT student_applications.id) as application_count'),
                DB::raw("COUNT(DISTINCT CASE WHEN genders.title = 'Male' THEN student_applications.id END) as male_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN genders.title = 'Female' THEN student_applications.id END) as female_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN students.disability_status = 'yes' THEN student_applications.id END) as disabled_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN mode_of_studies.name = 'Full Time' THEN student_applications.id END) as full_time_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN mode_of_studies.name = 'Part Time' THEN student_applications.id END) as part_time_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN mode_of_studies.name = 'Block Release' THEN student_applications.id END) as block_release_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN mode_of_studies.name = 'Ojet' THEN student_applications.id END) as ojet_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN class_lists.type = 'provisional' THEN student_applications.id END) as provisional_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN class_lists.type = 'verified' THEN student_applications.id END) as verified_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN class_lists.type = 'waiting' THEN student_applications.id END) as waiting_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN class_lists.type = 'final' THEN student_applications.id END) as final_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN class_lists.type = 'failed' THEN student_applications.id END) as failed_count"),
                DB::raw('COALESCE(class_sizes.total_class_size, 0) as total_class_size'),
            )
            ->join('institution_departments', function ($join): void {
                $join->on('student_applications.institution_department_id', '=', 'institution_departments.id')
                    ->whereNull('institution_departments.deleted_at');
            })
            ->join('departments', function ($join): void {
                $join->on('institution_departments.department_id', '=', 'departments.id')
                    ->whereNull('departments.deleted_at')
                    ->where('departments.is_academic', true);
            })
            ->leftJoin('students', 'student_applications.student_id', '=', 'students.id')
            ->leftJoin('genders', 'students.gender_id', '=', 'genders.id')
            ->leftJoin('mode_of_studies', 'student_applications.mode_of_study_id', '=', 'mode_of_studies.id')
            ->leftJoin('class_lists', function ($join): void {
                $join->on('class_lists.student_application_id', '=', 'student_applications.id')
                    ->whereNull('class_lists.deleted_at');
            })
            ->leftJoinSub($classSizesSub, 'class_sizes', function ($join): void {
                $join->on('class_sizes.institution_department_id', '=', 'institution_departments.id');
            })
            ->where('student_applications.intake_period_id', $intakePeriod->id)
            ->whereNull('student_applications.deleted_at');

        if ($this->isDepartmentUser) {
            $query->whereIn('institution_departments.id', $this->userDepartments);
        }

        $rows = $query
            ->groupBy(
                'institution_departments.id',
                'departments.id',
                'departments.name',
                'class_sizes.total_class_size',
            )
            ->orderBy('departments.name')
            ->get();

        $unassigned = $this->unassignedApplicationsByDepartmentMetrics($intakePeriod->id);

        if ($unassigned !== null) {
            $rows->push($unassigned);
        }

        return $rows;
    }

    public function applicationsByLevel(): Collection
    {
        $intakePeriod = Helper::resolveIntakePeriod();

        if ($this->isDepartmentUser && empty($this->userDepartments)) {
            return collect();
        }

        $query = DB::table('levels')
            ->select(
                'levels.id as level_id',
                'levels.name as level_name',
                DB::raw('COUNT(student_applications.id) as level_count'),
            )
            ->leftJoin('department_levels', 'department_levels.level_id', '=', 'levels.id')
            ->leftJoin('student_applications', function ($join) use ($intakePeriod) {
                $join->on('student_applications.department_level_id', '=', 'department_levels.id')
                    ->when($intakePeriod, function ($q) use ($intakePeriod) {
                        $q->where('student_applications.intake_period_id', $intakePeriod->id);
                    });
            });

        if ($this->isDepartmentUser) {
            $query->whereIn('department_levels.institution_department_id', $this->userDepartments);
        }

        return $query
            ->groupBy('levels.id', 'levels.name')
            ->orderBy('levels.name')
            ->get();
    }

    public function getDailyCountStats(): Collection
    {
        $intakePeriod = Helper::resolveIntakePeriod();

        if (! $intakePeriod) {
            return collect();
        }

        if ($this->isDepartmentUser && empty($this->userDepartments)) {
            return collect();
        }

        $query = DB::table('student_applications')
            ->selectRaw('DATE(student_applications.created_at) as count_date, COUNT(student_applications.id) as daily_count')
            ->where('student_applications.intake_period_id', $intakePeriod->id)
            ->whereBetween('student_applications.created_at', [
                $intakePeriod->start_date,
                Carbon::parse($intakePeriod->end_date)->addDay()->endOfDay()->toDateTimeString(),
            ])
            ->whereNull('student_applications.deleted_at');

        if ($this->isDepartmentUser) {
            $query->whereIn('student_applications.institution_department_id', $this->userDepartments);
        }

        $rawCounts = $query
            ->groupByRaw('DATE(student_applications.created_at)')
            ->orderBy('count_date')
            ->pluck('daily_count', 'count_date');

        $period = CarbonPeriod::create(
            $intakePeriod->start_date,
            Carbon::parse($intakePeriod->end_date)->addDay()->endOfDay()->toDateString()
        );

        return collect($period)->map(function ($date) use ($rawCounts) {
            return [
                'date' => $date->toDateString(),
                'count' => $rawCounts[$date->toDateString()] ?? 0,
            ];
        });
    }

    /**
     * @return array{applications: int, offersMade: int, confirmed: int, waitlisted: int, provisional: int, failedRejected: int}
     */
    public function enrolmentSummaryMetrics(): array
    {
        $intakePeriod = Helper::resolveIntakePeriod();

        $empty = [
            'applications' => 0,
            'offersMade' => 0,
            'confirmed' => 0,
            'waitlisted' => 0,
            'provisional' => 0,
            'failedRejected' => 0,
        ];

        if (! $intakePeriod || ($this->isDepartmentUser && empty($this->userDepartments))) {
            return $empty;
        }

        $departmentKey = $this->isDepartmentUser
            ? implode(',', $this->userDepartments)
            : 'all';

        $cacheKey = sprintf('enrolment_summary_metrics:%d:%s', $intakePeriod->id, $departmentKey);

        return Cache::remember($cacheKey, 60, function () use ($intakePeriod): array {
            $applications = $this->studentApplicationsBaseQuery($intakePeriod->id)->count();

            $offersMade = $this->studentApplicationsBaseQuery($intakePeriod->id)
                ->join('department_application_steps', 'student_applications.department_application_step_id', '=', 'department_application_steps.id')
                ->join('workflow_steps', 'department_application_steps.workflow_step_id', '=', 'workflow_steps.id')
                ->whereIn('workflow_steps.slug', [
                    WorkflowStepEnum::ACCEPTED->slug(),
                    WorkflowStepEnum::ENROLLED->slug(),
                ])
                ->count();

            $waitlisted = $this->studentApplicationsBaseQuery($intakePeriod->id)
                ->join('department_application_steps', 'student_applications.department_application_step_id', '=', 'department_application_steps.id')
                ->join('workflow_steps', 'department_application_steps.workflow_step_id', '=', 'workflow_steps.id')
                ->where('workflow_steps.slug', WorkflowStepEnum::WAITLISTED->slug())
                ->count();

            $confirmed = $this->studentApplicationsBaseQuery($intakePeriod->id)
                ->whereExists(function (Builder $query): void {
                    $query->select(DB::raw(1))
                        ->from('class_lists')
                        ->whereColumn('class_lists.student_application_id', 'student_applications.id')
                        ->whereNull('class_lists.deleted_at')
                        ->where('class_lists.attributes->identity_confirmed', true)
                        ->where('class_lists.attributes->disability_confirmed', true)
                        ->where('class_lists.attributes->names_confirmed', true);
                })
                ->count();

            $provisional = $this->studentApplicationsBaseQuery($intakePeriod->id)
                ->whereExists(function (Builder $query): void {
                    $query->select(DB::raw(1))
                        ->from('class_lists')
                        ->whereColumn('class_lists.student_application_id', 'student_applications.id')
                        ->whereNull('class_lists.deleted_at')
                        ->where('class_lists.type', ClassListTypeEnum::PROVISIONAL->value);
                })
                ->count();

            $failedRejected = $this->studentApplicationsBaseQuery($intakePeriod->id)
                ->whereExists(function (Builder $query): void {
                    $query->select(DB::raw(1))
                        ->from('class_lists')
                        ->whereColumn('class_lists.student_application_id', 'student_applications.id')
                        ->whereNull('class_lists.deleted_at')
                        ->where('class_lists.type', ClassListTypeEnum::FAILED->value);
                })
                ->count();

            return [
                'applications' => $applications,
                'offersMade' => $offersMade,
                'confirmed' => $confirmed,
                'waitlisted' => $waitlisted,
                'provisional' => $provisional,
                'failedRejected' => $failedRejected,
            ];
        });
    }

    private function studentApplicationsBaseQuery(int $intakePeriodId): Builder
    {
        $query = DB::table('student_applications')
            ->where('student_applications.intake_period_id', $intakePeriodId)
            ->whereNull('student_applications.deleted_at');

        if ($this->isDepartmentUser) {
            $query->whereIn('student_applications.institution_department_id', $this->userDepartments);
        }

        return $query;
    }

    private function unassignedApplicationsBaseQuery(int $intakePeriodId): Builder
    {
        $query = $this->studentApplicationsBaseQuery($intakePeriodId)
            ->whereNotExists(function (Builder $exists): void {
                $exists->select(DB::raw(1))
                    ->from('institution_departments')
                    ->join('departments', 'departments.id', '=', 'institution_departments.department_id')
                    ->whereColumn('institution_departments.id', 'student_applications.institution_department_id')
                    ->whereNull('institution_departments.deleted_at')
                    ->whereNull('departments.deleted_at')
                    ->where('departments.is_academic', true);
            });

        if ($this->isDepartmentUser) {
            return $query->whereRaw('0 = 1');
        }

        return $query;
    }

    private function unassignedApplicationsByDepartmentMetrics(int $intakePeriodId): ?object
    {
        $base = $this->unassignedApplicationsBaseQuery($intakePeriodId);

        if ((clone $base)->count() === 0) {
            return null;
        }

        $metrics = (clone $base)
            ->leftJoin('students', 'student_applications.student_id', '=', 'students.id')
            ->leftJoin('genders', 'students.gender_id', '=', 'genders.id')
            ->leftJoin('mode_of_studies', 'student_applications.mode_of_study_id', '=', 'mode_of_studies.id')
            ->leftJoin('class_lists', function ($join): void {
                $join->on('class_lists.student_application_id', '=', 'student_applications.id')
                    ->whereNull('class_lists.deleted_at');
            })
            ->select(
                DB::raw('COUNT(DISTINCT student_applications.id) as application_count'),
                DB::raw("COUNT(DISTINCT CASE WHEN genders.title = 'Male' THEN student_applications.id END) as male_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN genders.title = 'Female' THEN student_applications.id END) as female_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN students.disability_status = 'yes' THEN student_applications.id END) as disabled_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN mode_of_studies.name = 'Full Time' THEN student_applications.id END) as full_time_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN mode_of_studies.name = 'Part Time' THEN student_applications.id END) as part_time_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN mode_of_studies.name = 'Block Release' THEN student_applications.id END) as block_release_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN mode_of_studies.name = 'Ojet' THEN student_applications.id END) as ojet_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN class_lists.type = 'provisional' THEN student_applications.id END) as provisional_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN class_lists.type = 'verified' THEN student_applications.id END) as verified_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN class_lists.type = 'waiting' THEN student_applications.id END) as waiting_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN class_lists.type = 'final' THEN student_applications.id END) as final_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN class_lists.type = 'failed' THEN student_applications.id END) as failed_count"),
            )
            ->first();

        return (object) [
            'institution_department_id' => 0,
            'department_id' => 0,
            'department_name' => __('trans.ui_unassigned'),
            'application_count' => (int) $metrics->application_count,
            'male_count' => (int) $metrics->male_count,
            'female_count' => (int) $metrics->female_count,
            'disabled_count' => (int) $metrics->disabled_count,
            'full_time_count' => (int) $metrics->full_time_count,
            'part_time_count' => (int) $metrics->part_time_count,
            'block_release_count' => (int) $metrics->block_release_count,
            'ojet_count' => (int) $metrics->ojet_count,
            'provisional_count' => (int) $metrics->provisional_count,
            'verified_count' => (int) $metrics->verified_count,
            'waiting_count' => (int) $metrics->waiting_count,
            'final_count' => (int) $metrics->final_count,
            'failed_count' => (int) $metrics->failed_count,
            'total_class_size' => 0,
        ];
    }
}
