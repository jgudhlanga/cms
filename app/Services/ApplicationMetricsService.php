<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationMetricsService
{

    public function __construct()
    {

    }

    public function applicationsByDepartment(): Collection
    {
        return DB::table('departments')
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
            ->groupBy('departments.id', 'departments.name')
            ->get();
    }

    public function applicationsByLevel(): Collection
    {
        return DB::table('levels')
            ->select(
                'levels.id as level_id',
                'levels.name as level_name',
                DB::raw('COUNT(student_programs.id) as level_count'),
            )
            ->leftJoin('department_levels', 'department_levels.level_id', '=', 'levels.id')
            ->leftJoin('student_programs', 'student_programs.department_level_id', '=', 'department_levels.id')
            ->groupBy('levels.id', 'levels.name')
            ->get();
    }
}
