<?php

namespace App\Importers\Institution;

use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\CourseSyllabus;
use Illuminate\Support\Facades\Log;
use LaravelIngest\Contracts\IngestDefinition;
use LaravelIngest\Enums\DuplicateStrategy;
use LaravelIngest\Enums\SourceType;
use LaravelIngest\IngestConfig;
use RuntimeException;

class CourseSyllabusImporter implements IngestDefinition
{
    private const int TENANT_ID = 1;

    public function getConfig(): IngestConfig
    {
        return IngestConfig::for(CourseSyllabus::class)
            ->fromSource(SourceType::FILESYSTEM, [
                'disk' => 'ingest',
                'path' => 'syllabus.xlsx',
            ])
            ->keyedBy('COURSE_CODE')
            ->onDuplicate(DuplicateStrategy::SKIP)
            ->beforeRow(function (array &$row): void {
                $row['__TENANT_ID'] = self::TENANT_ID;
                self::logValidationIssues($row);
            })
            ->map('COURSE_TITLE', 'title')
            ->map('COURSE_CODE', 'code')
            ->map('IMPLEMENTATION_YEAR', 'implementation_year')
            ->map('__TENANT_ID', 'tenant_id')
            ->mapAndTransform(
                'DEPARTMENT',
                'institution_department_id',
                static fn (string $department): int => self::resolveInstitutionDepartmentId($department)
            )
            ->mapAndTransform(
                'LEVEL',
                'department_level_course_id',
                static fn (string $level, array $row): int => self::resolveDepartmentLevelCourseId(
                    department: (string) ($row['DEPARTMENT'] ?? ''),
                    level: $level,
                    courseTitle: (string) ($row['COURSE_TITLE'] ?? '')
                )
            )
            ->validate([
                'DEPARTMENT' => ['required', 'string'],
                'LEVEL' => ['required', 'string'],
                'COURSE_TITLE' => ['required', 'string'],
                'COURSE_CODE' => ['required', 'string'],
                'IMPLEMENTATION_YEAR' => ['required'],
            ]);
    }

    private static function resolveInstitutionDepartmentId(string $department): int
    {
        $institutionDepartment = InstitutionDepartment::query()
            ->where('tenant_id', self::TENANT_ID)
            ->whereHas('department', function ($query) use ($department): void {
                $query->whereRaw('LOWER(name) = ?', [self::normalize($department)]);
            })
            ->first();

        if ($institutionDepartment === null) {
            Log::warning('Syllabus import lookup failed: institution department missing.', [
                'tenant_id' => self::TENANT_ID,
                'department' => $department,
            ]);
            throw new RuntimeException("Institution department not found for DEPARTMENT '{$department}'.");
        }

        return $institutionDepartment->id;
    }

    private static function resolveDepartmentLevelCourseId(string $department, string $level, string $courseTitle): int
    {
        $institutionDepartmentId = self::resolveInstitutionDepartmentId($department);

        $departmentLevel = DepartmentLevel::query()
            ->where('tenant_id', self::TENANT_ID)
            ->where('institution_department_id', $institutionDepartmentId)
            ->whereHas('level', function ($query) use ($level): void {
                $query->whereRaw('LOWER(name) = ?', [self::normalize($level)]);
            })
            ->first();

        if ($departmentLevel === null) {
            Log::warning('Syllabus import lookup failed: department level missing.', [
                'tenant_id' => self::TENANT_ID,
                'department' => $department,
                'level' => $level,
            ]);
            throw new RuntimeException("Department level not found for LEVEL '{$level}' in DEPARTMENT '{$department}'.");
        }

        $departmentCourse = DepartmentCourse::query()
            ->where('tenant_id', self::TENANT_ID)
            ->where('institution_department_id', $institutionDepartmentId)
            ->whereHas('course', function ($query) use ($courseTitle): void {
                $query->whereRaw('LOWER(name) = ?', [self::normalize($courseTitle)]);
            })
            ->first();

        if ($departmentCourse === null) {
            Log::warning('Syllabus import lookup failed: department course missing.', [
                'tenant_id' => self::TENANT_ID,
                'department' => $department,
                'course_title' => $courseTitle,
            ]);
            throw new RuntimeException("Department course not found for COURSE_TITLE '{$courseTitle}' in DEPARTMENT '{$department}'.");
        }

        $departmentLevelCourse = DepartmentLevelCourse::query()
            ->where('department_level_id', $departmentLevel->id)
            ->where('department_course_id', $departmentCourse->id)
            ->first();

        if ($departmentLevelCourse === null) {
            Log::warning('Syllabus import lookup failed: department level course missing.', [
                'tenant_id' => self::TENANT_ID,
                'department' => $department,
                'level' => $level,
                'course_title' => $courseTitle,
            ]);
            throw new RuntimeException(
                "Department level course not found for DEPARTMENT '{$department}', LEVEL '{$level}', COURSE_TITLE '{$courseTitle}'."
            );
        }

        return $departmentLevelCourse->id;
    }

    private static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private static function logValidationIssues(array $row): void
    {
        $requiredFields = ['DEPARTMENT', 'LEVEL', 'COURSE_TITLE', 'COURSE_CODE', 'IMPLEMENTATION_YEAR'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            $value = $row[$field] ?? null;

            if ($value === null || trim((string) $value) === '') {
                $missingFields[] = $field;
            }
        }

        if ($missingFields !== []) {
            Log::warning('Syllabus import validation failed: missing required fields.', [
                'tenant_id' => self::TENANT_ID,
                'missing_fields' => $missingFields,
                'row' => $row,
            ]);
        }
    }
}
