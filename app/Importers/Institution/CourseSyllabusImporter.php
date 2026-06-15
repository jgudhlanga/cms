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
    public const string IMPORTER_NAME = 'course-syllabus-import';

    public const int HEADER_ROW = 5;

    public const int DATA_START_ROW = 6;

    /** @var list<string> */
    public const array WEB_COLUMNS = [
        'LEVEL',
        'COURSE_TITLE',
        'COURSE_CODE',
        'SEMESTER',
        'MODULE_TITLE',
        'MODULE_CODE',
    ];

    /** @var list<string> */
    public const array CLI_COLUMNS = [
        'DEPARTMENT',
        'LEVEL',
        'COURSE_TITLE',
        'COURSE_CODE',
        'SEMESTER',
        'MODULE_TITLE',
        'MODULE_CODE',
    ];

    public function __construct(
        private int $tenantId = 1,
        private ?int $institutionDepartmentId = null,
        private bool $fromFilesystem = true,
    ) {}

    public function getConfig(): IngestConfig
    {
        $config = IngestConfig::for(CourseSyllabus::class)
            ->keyedBy('COURSE_CODE')
            ->onDuplicate(DuplicateStrategy::UPDATE)
            ->beforeRow(function (array &$row): void {
                $row['__TENANT_ID'] = $this->tenantId;

                if ($this->isWebImport()) {
                    $row['__INSTITUTION_DEPARTMENT_ID'] = $this->institutionDepartmentId;
                } else {
                    $row['__INSTITUTION_DEPARTMENT_ID'] = self::resolveInstitutionDepartmentId(
                        $this->tenantId,
                        (string) ($row['DEPARTMENT'] ?? ''),
                    );
                }

                self::logValidationIssues($row, $this->isWebImport());

                $courseCode = trim((string) ($row['COURSE_CODE'] ?? ''));

                if ($courseCode !== '') {
                    $row['__IMPLEMENTATION_YEAR'] = self::implementationYearFromCourseCode($courseCode, $this->tenantId);
                }
            })
            ->map('COURSE_TITLE', 'title')
            ->map('COURSE_CODE', 'code')
            ->map('__IMPLEMENTATION_YEAR', 'implementation_year')
            ->map('__TENANT_ID', 'tenant_id')
            ->mapAndTransform(
                '__INSTITUTION_DEPARTMENT_ID',
                'institution_department_id',
                fn (mixed $departmentId): int => (int) $departmentId,
            )
            ->mapAndTransform(
                'LEVEL',
                'department_level_course_id',
                fn (string $level, array $row): int => self::resolveDepartmentLevelCourseId(
                    tenantId: $this->tenantId,
                    institutionDepartmentId: (int) ($row['__INSTITUTION_DEPARTMENT_ID'] ?? 0),
                    level: $level,
                    courseTitle: (string) ($row['COURSE_TITLE'] ?? ''),
                )
            )
            ->validate($this->validationRules());

        if ($this->fromFilesystem) {
            return $config->fromSource(SourceType::FILESYSTEM, [
                'disk' => 'ingest',
                'path' => 'syllabus.xlsx',
            ]);
        }

        return $config;
    }

    /**
     * @param  list<mixed>  $headerRow
     */
    public static function isHeaderRow(array $headerRow): bool
    {
        $normalized = array_map(
            static fn (mixed $value): string => strtoupper(trim((string) $value)),
            array_values($headerRow),
        );

        return self::matchesColumnSet($normalized, self::WEB_COLUMNS)
            || self::matchesColumnSet($normalized, self::CLI_COLUMNS);
    }

    /**
     * @param  list<string>  $normalized
     * @param  list<string>  $columns
     */
    private static function matchesColumnSet(array $normalized, array $columns): bool
    {
        foreach ($columns as $index => $column) {
            if (($normalized[$index] ?? '') !== $column) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<mixed>  $rawRow
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    public static function rowToAssociative(array $rawRow, array $columns = self::WEB_COLUMNS): array
    {
        $associative = [];

        foreach ($columns as $index => $column) {
            $associative[$column] = $rawRow[$index] ?? null;
        }

        return $associative;
    }

    /**
     * @return array<string, list<string>>
     */
    private function validationRules(): array
    {
        if ($this->isWebImport()) {
            return [
                'LEVEL' => ['required', 'string'],
                'COURSE_TITLE' => ['required', 'string'],
                'COURSE_CODE' => ['required', 'string'],
            ];
        }

        return [
            'DEPARTMENT' => ['required', 'string'],
            'LEVEL' => ['required', 'string'],
            'COURSE_TITLE' => ['required', 'string'],
            'COURSE_CODE' => ['required', 'string'],
        ];
    }

    private function isWebImport(): bool
    {
        return $this->institutionDepartmentId !== null;
    }

    private static function resolveInstitutionDepartmentId(int $tenantId, string $department): int
    {
        $institutionDepartment = InstitutionDepartment::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('department', function ($query) use ($department): void {
                $query->whereRaw('LOWER(name) = ?', [self::normalize($department)]);
            })
            ->first();

        if ($institutionDepartment === null) {
            Log::warning('Syllabus import lookup failed: institution department missing.', [
                'tenant_id' => $tenantId,
                'department' => $department,
            ]);
            throw new RuntimeException("Institution department not found for DEPARTMENT '{$department}'.");
        }

        return $institutionDepartment->id;
    }

    private static function resolveDepartmentLevelCourseId(
        int $tenantId,
        int $institutionDepartmentId,
        string $level,
        string $courseTitle,
    ): int {
        $departmentLevel = DepartmentLevel::query()
            ->where('tenant_id', $tenantId)
            ->where('institution_department_id', $institutionDepartmentId)
            ->whereHas('level', function ($query) use ($level): void {
                $query->whereRaw('LOWER(name) = ?', [self::normalize($level)]);
            })
            ->first();

        if ($departmentLevel === null) {
            Log::warning('Syllabus import lookup failed: department level missing.', [
                'tenant_id' => $tenantId,
                'institution_department_id' => $institutionDepartmentId,
                'level' => $level,
            ]);
            throw new RuntimeException("Department level not found for LEVEL '{$level}'.");
        }

        $departmentCourse = DepartmentCourse::query()
            ->where('tenant_id', $tenantId)
            ->where('institution_department_id', $institutionDepartmentId)
            ->whereHas('course', function ($query) use ($courseTitle): void {
                $query->whereRaw('LOWER(name) = ?', [self::normalize($courseTitle)]);
            })
            ->first();

        if ($departmentCourse === null) {
            Log::warning('Syllabus import lookup failed: department course missing.', [
                'tenant_id' => $tenantId,
                'institution_department_id' => $institutionDepartmentId,
                'course_title' => $courseTitle,
            ]);
            throw new RuntimeException("Department course not found for COURSE_TITLE '{$courseTitle}'.");
        }

        $departmentLevelCourse = DepartmentLevelCourse::query()
            ->where('department_level_id', $departmentLevel->id)
            ->where('department_course_id', $departmentCourse->id)
            ->first();

        if ($departmentLevelCourse === null) {
            Log::warning('Syllabus import lookup failed: department level course missing.', [
                'tenant_id' => $tenantId,
                'institution_department_id' => $institutionDepartmentId,
                'level' => $level,
                'course_title' => $courseTitle,
            ]);
            throw new RuntimeException(
                "Department level course not found for LEVEL '{$level}', COURSE_TITLE '{$courseTitle}'."
            );
        }

        return $departmentLevelCourse->id;
    }

    private static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private static function implementationYearFromCourseCode(string $courseCode, int $tenantId): string
    {
        $parts = array_map(static fn (string $value): string => trim($value), explode('/', $courseCode));
        $yearSegment = $parts[1] ?? null;

        if ($yearSegment === null || ! preg_match('/^\d{2}$/', $yearSegment)) {
            Log::warning('Syllabus import lookup failed: unable to derive implementation year from course code.', [
                'tenant_id' => $tenantId,
                'course_code' => $courseCode,
            ]);

            throw new RuntimeException(
                "Invalid COURSE_CODE '{$courseCode}'. Expected second slash-separated segment to be a two-digit year."
            );
        }

        return "20{$yearSegment}";
    }

    private static function logValidationIssues(array $row, bool $webImport): void
    {
        $requiredFields = $webImport
            ? ['LEVEL', 'COURSE_TITLE', 'COURSE_CODE']
            : ['DEPARTMENT', 'LEVEL', 'COURSE_TITLE', 'COURSE_CODE'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            $value = $row[$field] ?? null;

            if ($value === null || trim((string) $value) === '') {
                $missingFields[] = $field;
            }
        }

        if ($missingFields !== []) {
            Log::warning('Syllabus import validation failed: missing required fields.', [
                'missing_fields' => $missingFields,
                'row' => $row,
            ]);
        }
    }
}
