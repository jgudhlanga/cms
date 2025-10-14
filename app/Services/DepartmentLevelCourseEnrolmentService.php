<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentLevelCourseEnrolmentService
{

    public function __construct()
    {

    }

    public function applicationsByDepartment(): Collection
    {
        return DB::table('departments')
            ->select(
                'departments.id as departmentId',
                'departments.name as departmentName',
                DB::raw('COUNT(student_programs.id) as applicationCount'),
                DB::raw("SUM(CASE WHEN students.gender_id = (SELECT id FROM genders WHERE title = 'Male' LIMIT 1) THEN 1 ELSE 0 END) as maleCount"),
                DB::raw("SUM(CASE WHEN students.gender_id = (SELECT id FROM genders WHERE title = 'Female' LIMIT 1) THEN 1 ELSE 0 END) as femaleCount"),
                DB::raw("SUM(CASE WHEN students.disability_status = 'yes' THEN 1 ELSE 0 END) as disabledCount")
            )
            ->leftJoin('institution_departments', 'institution_departments.department_id', '=', 'departments.id')
            ->leftJoin('student_programs', 'student_programs.institution_department_id', '=', 'institution_departments.id')
            ->leftJoin('students', 'student_programs.student_id', '=', 'students.id')
            ->where('departments.is_academic', true)
            ->groupBy('departments.id', 'departments.name')
            ->get();
    }
}
