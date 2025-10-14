<?php

namespace App\Services;

use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentLevelCourseEnrolmentService
{
    /**
     * Base reusable raw SQL query with flexible conditions.
     */
    protected function baseEnrolmentQuery(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevel $departmentLevel,
        ?int $intakePeriodId,
        ?int $modeOfStudyId,
        ?int $courseId,
        ?string $disabilityStatus = null,
        bool $excludeDisability = false
    ): array {
        $bindings = [
            'department_id' => $institutionDepartment->id,
            'department_level_id' => $departmentLevel->id,
        ];

        $whereClauses = [
            'sp.institution_department_id = :department_id',
            'sp.department_level_id = :department_level_id',
        ];

        if ($intakePeriodId) {
            $whereClauses[] = 'sp.intake_period_id = :intake_period_id';
            $bindings['intake_period_id'] = $intakePeriodId;
        }

        if ($modeOfStudyId) {
            $whereClauses[] = 'sp.mode_of_study_id = :mode_of_study_id';
            $bindings['mode_of_study_id'] = $modeOfStudyId;
        }

        if ($courseId) {
            $whereClauses[] = 'sp.department_course_id = :course_id';
            $bindings['course_id'] = $courseId;
        }

        if ($disabilityStatus) {
            // Include only a specific disability status
            $whereClauses[] = 's.disability_status = :disability_status';
            $bindings['disability_status'] = $disabilityStatus;
        }

        if ($excludeDisability) {
            // Exclude disability_status = 'yes'
            $whereClauses[] = "(s.disability_status IS NULL OR s.disability_status != 'yes')";
        }

        $sql = "
            SELECT
                sp.id AS enrolment_id,
                sp.created_at AS enrolment_created_at,
                sp.intake_period_id,
                sp.mode_of_study_id,
                sp.department_course_id,

                ws.name AS workflow_step_name,
                dws.position AS workflow_step_position,

                s.id AS student_id,
                s.disability_status,
                u.id AS user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.gender,

                dc.id AS department_course_id,
                c.name AS course_name,

                dl.id AS department_level_id,
                l.name AS level_name,

                dep.id AS department_id,
                dep.name AS department_name

            FROM student_programs sp
            INNER JOIN department_workflow_steps dws ON dws.id = sp.department_workflow_step_id
            INNER JOIN workflow_steps ws ON ws.id = dws.workflow_step_id
            INNER JOIN students s ON s.id = sp.student_id
            INNER JOIN users u ON u.id = s.user_id
            INNER JOIN institution_departments idep ON idep.id = sp.institution_department_id
            INNER JOIN departments dep ON dep.id = idep.department_id
            INNER JOIN department_levels dl ON dl.id = sp.department_level_id
            INNER JOIN levels l ON l.id = dl.level_id
            INNER JOIN department_courses dc ON dc.id = sp.department_course_id
            INNER JOIN courses c ON c.id = dc.course_id

            LEFT JOIN o_level_results olr ON olr.student_id = s.id

            WHERE " . implode(' AND ', $whereClauses) . "
            ORDER BY sp.created_at ASC
        ";

        return DB::select($sql, $bindings);
    }

    /**
     * Fetch enrolments where disability_status = 'yes'
     */
    public function fetchEnrolmentsByDisability(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevel $departmentLevel,
        ?int $intakePeriodId,
        ?int $modeOfStudyId,
        ?int $courseId
    ): Collection
    {
        $rows = $this->baseEnrolmentQuery(
            $institutionDepartment,
            $departmentLevel,
            $intakePeriodId,
            $modeOfStudyId,
            $courseId,
            'yes' // only disabled students
        );

        $grouped = collect($rows)
            ->groupBy('workflow_step_name')
            ->sortByDesc(fn($group) => $group->first()->workflow_step_position ?? 0);

        return $grouped->map(fn($group) => EnrolmentResource::collection(collect($group)));
    }

    /**
     * Fetch enrolments grouped by gender, excluding students with disability_status = 'yes'
     */
    public function fetchEnrolmentsByGender(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevel $departmentLevel,
        ?int $intakePeriodId,
        ?int $modeOfStudyId,
        ?int $courseId
    ): Collection
    {
        $rows = $this->baseEnrolmentQuery(
            $institutionDepartment,
            $departmentLevel,
            $intakePeriodId,
            $modeOfStudyId,
            $courseId,
            disabilityStatus: null,
            excludeDisability: true
        );

        return collect($rows)
            ->groupBy(fn($row) => ucfirst(strtolower($row->gender ?? 'Unknown')))
            ->map(fn($group) => [
                'count' => $group->count(),
                'enrolments' => EnrolmentResource::collection(collect($group)),
            ])
            ->sortKeys();
    }
}
