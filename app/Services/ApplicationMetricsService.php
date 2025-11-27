<?php

namespace App\Services;

use App\Enums\Acl\PermissionEnum;
use App\Helpers\Helper;
use App\Models\Institution\Staff;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
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

    /*public function applicationsByDepartment(): Collection
    {
        $intakePeriod = Helper::resolveIntakePeriod();

        if ($this->isDepartmentUser && empty($this->userDepartments)) {
            return collect();
        }

        $query = DB::table('departments')
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',

                DB::raw('COUNT(student_programs.id) as application_count'),

                // gender counts
                DB::raw("SUM(CASE WHEN genders.title = 'Male' THEN 1 ELSE 0 END) as male_count"),
                DB::raw("SUM(CASE WHEN genders.title = 'Female' THEN 1 ELSE 0 END) as female_count"),

                // disability counts
                DB::raw("SUM(CASE WHEN students.disability_status = 'yes' THEN 1 ELSE 0 END) as disabled_count"),

                // mode of study counts
                DB::raw("SUM(CASE WHEN mode_of_studies.name = 'Full Time' THEN 1 ELSE 0 END) as full_time_count"),
                DB::raw("SUM(CASE WHEN mode_of_studies.name = 'Part Time' THEN 1 ELSE 0 END) as part_time_count"),
                DB::raw("SUM(CASE WHEN mode_of_studies.name = 'Block Release' THEN 1 ELSE 0 END) as block_release_count"),
                DB::raw("SUM(CASE WHEN mode_of_studies.name = 'Ojet' THEN 1 ELSE 0 END) as ojet_count"),

                // class_list counts
                DB::raw("SUM(CASE WHEN class_lists.type = 'provisional' THEN 1 ELSE 0 END) as provisional_count"),
                DB::raw("SUM(CASE WHEN class_lists.type = 'verified' THEN 1 ELSE 0 END) as verified_count"),
                DB::raw("SUM(CASE WHEN class_lists.type = 'waiting' THEN 1 ELSE 0 END) as waiting_count"),
                DB::raw("SUM(CASE WHEN class_lists.type = 'final' THEN 1 ELSE 0 END) as final_count"),
                DB::raw("SUM(CASE WHEN class_lists.type = 'failed' THEN 1 ELSE 0 END) as failed_count")
            )

            // JOINS
            ->leftJoin('institution_departments', 'institution_departments.department_id', '=', 'departments.id')
            ->leftJoin('student_programs', 'student_programs.institution_department_id', '=', 'institution_departments.id')
            ->leftJoin('students', 'student_programs.student_id', '=', 'students.id')
            ->leftJoin('genders', 'students.gender_id', '=', 'genders.id')
            ->leftJoin('mode_of_studies', 'student_programs.mode_of_study_id', '=', 'mode_of_studies.id')
            ->leftJoin('class_lists', 'class_lists.student_program_id', '=', 'student_programs.id')

            // FILTERS
            ->where('departments.is_academic', true)
            ->where('student_programs.intake_period_id', $intakePeriod?->id);

        if ($this->isDepartmentUser) {
            $query->whereIn('institution_departments.id', $this->userDepartments);
        }

        return $query
            ->groupBy('departments.id', 'departments.name')
            ->get();
    }*/

    public function applicationsByDepartment(): Collection
    {
        $intakePeriod = Helper::resolveIntakePeriod();

        if ($this->isDepartmentUser && empty($this->userDepartments)) {
            return collect();
        }

        // pre-aggregate class sizes so join doesn't multiply rows
        $classSizesSub = DB::table('department_intake_class_sizes')
            ->select(
                'institution_department_id',
                DB::raw('SUM(class_size) as total_class_size')
            )
            ->where('intake_period_id', $intakePeriod?->id)
            ->groupBy('institution_department_id');

        $query = DB::table('departments')
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',

                // use DISTINCT to avoid duplicates if joins cause repeats
                DB::raw('COUNT(DISTINCT student_programs.id) as application_count'),

                // gender counts (use SUM of DISTINCT student_program id conditions would be complicated;
                // keeping SUM(CASE WHEN ...) is fine if duplicates are removed by DISTINCT count above,
                // but if you still see issues switch to SUM(DISTINCT ...) patterns per need)
                DB::raw("SUM(CASE WHEN genders.title = 'Male' THEN 1 ELSE 0 END) as male_count"),
                DB::raw("SUM(CASE WHEN genders.title = 'Female' THEN 1 ELSE 0 END) as female_count"),

                // disability counts
                DB::raw("SUM(CASE WHEN students.disability_status = 'yes' THEN 1 ELSE 0 END) as disabled_count"),

                // mode of study counts
                DB::raw("SUM(CASE WHEN mode_of_studies.name = 'Full Time' THEN 1 ELSE 0 END) as full_time_count"),
                DB::raw("SUM(CASE WHEN mode_of_studies.name = 'Part Time' THEN 1 ELSE 0 END) as part_time_count"),
                DB::raw("SUM(CASE WHEN mode_of_studies.name = 'Block Release' THEN 1 ELSE 0 END) as block_release_count"),
                DB::raw("SUM(CASE WHEN mode_of_studies.name = 'Ojet' THEN 1 ELSE 0 END) as ojet_count"),

                // class_list counts
                DB::raw("SUM(CASE WHEN class_lists.type = 'provisional' THEN 1 ELSE 0 END) as provisional_count"),
                DB::raw("SUM(CASE WHEN class_lists.type = 'verified' THEN 1 ELSE 0 END) as verified_count"),
                DB::raw("SUM(CASE WHEN class_lists.type = 'waiting' THEN 1 ELSE 0 END) as waiting_count"),
                DB::raw("SUM(CASE WHEN class_lists.type = 'final' THEN 1 ELSE 0 END) as final_count"),
                DB::raw("SUM(CASE WHEN class_lists.type = 'failed' THEN 1 ELSE 0 END) as failed_count"),

                // from the pre-aggregated subquery; coalesce to 0 when null
                DB::raw("COALESCE(class_sizes.total_class_size, 0) as total_class_size")
            )

            // JOINS
            ->leftJoin('institution_departments', 'institution_departments.department_id', '=', 'departments.id')
            ->leftJoin('student_programs', 'student_programs.institution_department_id', '=', 'institution_departments.id')
            ->leftJoin('students', 'student_programs.student_id', '=', 'students.id')
            ->leftJoin('genders', 'students.gender_id', '=', 'genders.id')
            ->leftJoin('mode_of_studies', 'student_programs.mode_of_study_id', '=', 'mode_of_studies.id')
            ->leftJoin('class_lists', 'class_lists.student_program_id', '=', 'student_programs.id')

            // join the aggregated class sizes subquery (one row per institution_department)
            ->leftJoinSub($classSizesSub, 'class_sizes', function ($join) {
                $join->on('class_sizes.institution_department_id', '=', 'institution_departments.id');
            })

            // FILTERS
            ->where('departments.is_academic', true)
            ->where('student_programs.intake_period_id', $intakePeriod?->id);

        if ($this->isDepartmentUser) {
            $query->whereIn('institution_departments.id', $this->userDepartments);
        }

        return $query
            ->groupBy('departments.id', 'departments.name', 'class_sizes.total_class_size')
            ->get();
    }

    public function applicationsByLevel(): Collection
    {
        $intakePeriod = Helper::resolveIntakePeriod();

        // 🔒 If restricted user and no departments, return empty
        if ($this->isDepartmentUser && empty($this->userDepartments)) {
            return collect();
        }

        $query = DB::table('levels')
            ->select(
                'levels.id as level_id',
                'levels.name as level_name',
                DB::raw('COUNT(student_programs.id) as level_count'),
            )
            ->leftJoin('department_levels', 'department_levels.level_id', '=', 'levels.id')
            ->leftJoin('student_programs', function ($join) use ($intakePeriod) {
                $join->on('student_programs.department_level_id', '=', 'department_levels.id')
                    ->when($intakePeriod, function ($q) use ($intakePeriod) {
                        $q->where('student_programs.intake_period_id', $intakePeriod->id);
                    });
            });

        // 🔒 Restrict to levels belonging to user's departments
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

        if (!$intakePeriod) {
            return collect();
        }

        // 🔒 If restricted user and no departments, return empty
        if ($this->isDepartmentUser && empty($this->userDepartments)) {
            return collect();
        }

        $query = DB::table('student_programs')
            ->selectRaw('DATE(student_programs.created_at) as count_date, COUNT(student_programs.id) as daily_count')
            ->whereBetween('student_programs.created_at', [
                $intakePeriod->start_date,
                Carbon::parse($intakePeriod->end_date)->addDay()->endOfDay()->toDateTimeString(),
            ]);

        if ($this->isDepartmentUser) {
            $query->whereIn('student_programs.institution_department_id', $this->userDepartments);
        }

        $rawCounts = $query
            ->groupByRaw('DATE(student_programs.created_at)')
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
}
