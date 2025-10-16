<?php

namespace App\Services;

use App\Enums\Acl\PermissionEnum;
use App\Helpers\Helper;
use App\Models\Institution\Staff;
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

    public function applicationsByDepartment(): Collection
    {
        $intakePeriod = Helper::resolveIntakePeriod();

        // 🔒 If restricted user and no departments, return empty
        if ($this->isDepartmentUser && empty($this->userDepartments)) {
            return collect();
        }

        $query = DB::table('departments')
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',
                DB::raw('COUNT(student_programs.id) as application_count'),
                DB::raw("SUM(CASE WHEN students.gender_id = (SELECT id FROM genders WHERE title = 'Male' LIMIT 1) THEN 1 ELSE 0 END) as male_count"),
                DB::raw("SUM(CASE WHEN students.gender_id = (SELECT id FROM genders WHERE title = 'Female' LIMIT 1) THEN 1 ELSE 0 END) as female_count"),
                DB::raw("SUM(CASE WHEN students.disability_status = 'yes' THEN 1 ELSE 0 END) as disabled_count"),
                DB::raw("SUM(CASE WHEN student_programs.mode_of_study_id = (SELECT id FROM mode_of_studies WHERE name = 'Full Time' LIMIT 1) THEN 1 ELSE 0 END) as full_time_count"),
                DB::raw("SUM(CASE WHEN student_programs.mode_of_study_id = (SELECT id FROM mode_of_studies WHERE name = 'Part Time' LIMIT 1) THEN 1 ELSE 0 END) as part_time_count"),
                DB::raw("SUM(CASE WHEN student_programs.mode_of_study_id = (SELECT id FROM mode_of_studies WHERE name = 'Block Release' LIMIT 1) THEN 1 ELSE 0 END) as block_release_count"),
                DB::raw("SUM(CASE WHEN student_programs.mode_of_study_id = (SELECT id FROM mode_of_studies WHERE name = 'Ojet' LIMIT 1) THEN 1 ELSE 0 END) as ojet_count"),
            )
            ->leftJoin('institution_departments', 'institution_departments.department_id', '=', 'departments.id')
            ->leftJoin('student_programs', 'student_programs.institution_department_id', '=', 'institution_departments.id')
            ->leftJoin('students', 'student_programs.student_id', '=', 'students.id')
            ->where('departments.is_academic', true)
            ->where('student_programs.intake_period_id', $intakePeriod?->id);

        if ($this->isDepartmentUser) {
            $query->whereIn('institution_departments.id', $this->userDepartments);
        }

        return $query
            ->groupBy('departments.id', 'departments.name')
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
                now()->endOfDay()->toDateTimeString(),
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
            now()->toDateString()
        );

        return collect($period)->map(function ($date) use ($rawCounts) {
            return [
                'date' => $date->toDateString(),
                'count' => $rawCounts[$date->toDateString()] ?? 0,
            ];
        });
    }
}
