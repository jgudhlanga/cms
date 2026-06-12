<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Importers\Institution\CourseSyllabusImporter;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\CourseSyllabus;

class CourseSyllabusImportTemplateService
{
    /**
     * @return array{
     *     header: array{generatedAt: string, tenantId: int, departmentCode: string, departmentName: string},
     *     lookups: array<string, list<string>>,
     *     rows: list<list<mixed>>,
     * }
     */
    public function assemble(int $institutionDepartmentId): array
    {
        $department = InstitutionDepartment::query()
            ->with('department')
            ->findOrFail($institutionDepartmentId);

        $tenantId = (int) $department->tenant_id;

        $levels = DepartmentLevel::query()
            ->where('institution_department_id', $institutionDepartmentId)
            ->with('level')
            ->get()
            ->map(fn (DepartmentLevel $departmentLevel): string => (string) ($departmentLevel->level?->name ?? ''))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $courses = DepartmentCourse::query()
            ->where('institution_department_id', $institutionDepartmentId)
            ->with('course')
            ->get()
            ->map(fn (DepartmentCourse $departmentCourse): string => (string) ($departmentCourse->course?->name ?? ''))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $levelCourses = DepartmentLevelCourse::query()
            ->whereHas('departmentLevel', fn ($query) => $query->where('institution_department_id', $institutionDepartmentId))
            ->with(['departmentLevel.level', 'departmentCourse.course'])
            ->get()
            ->map(function (DepartmentLevelCourse $link): string {
                $level = $link->departmentLevel?->level?->name ?? '';
                $course = $link->departmentCourse?->course?->name ?? '';

                return trim("{$level} - {$course}");
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $syllabusCodes = CourseSyllabus::query()
            ->where('institution_department_id', $institutionDepartmentId)
            ->orderBy('code')
            ->pluck('code')
            ->all();

        $semesters = AcademicYearOption::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $exampleRow = array_fill(0, count(CourseSyllabusImporter::WEB_COLUMNS), null);

        if ($levels !== [] && $courses !== []) {
            $exampleRow[0] = $levels[0];
            $exampleRow[1] = $courses[0];
            $exampleRow[2] = 'CODE/26/101';
            $exampleRow[3] = 'Semester 1';
            $exampleRow[4] = 'Module title';
            $exampleRow[5] = 'MOD-101';
        }

        return [
            'header' => [
                'generatedAt' => now()->format('d M Y'),
                'tenantId' => $tenantId,
                'departmentCode' => (string) $department->department_code,
                'departmentName' => (string) ($department->department?->name ?? ''),
            ],
            'lookups' => [
                'levels' => $levels,
                'courses' => $courses,
                'level_courses' => $levelCourses,
                'semesters' => $semesters,
                'syllabus_codes' => $syllabusCodes,
            ],
            'rows' => [$exampleRow],
        ];
    }

    public function downloadFileName(InstitutionDepartment $department): string
    {
        $code = strtolower((string) $department->department_code);

        return sprintf('syllabus-import-template-%s-%s.xlsx', $code, time());
    }
}
