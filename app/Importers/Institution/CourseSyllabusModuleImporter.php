<?php

namespace App\Importers\Institution;

use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Services\Institution\ResolveAcademicYearOptionFromImport;
use Illuminate\Support\Facades\Log;
use LaravelIngest\Contracts\IngestDefinition;
use LaravelIngest\Enums\DuplicateStrategy;
use LaravelIngest\Enums\SourceType;
use LaravelIngest\IngestConfig;
use RuntimeException;

class CourseSyllabusModuleImporter implements IngestDefinition
{
    public const string IMPORTER_NAME = 'course-syllabus-module-import';

    /** @var list<string> */
    public const array MODULE_COLUMNS = [
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
        $config = IngestConfig::for(CourseSyllabusModule::class)
            ->keyedBy(['COURSE_CODE', 'MODULE_CODE'])
            ->onDuplicate(DuplicateStrategy::UPDATE)
            ->beforeRow(function (array &$row): void {
                $row['__TENANT_ID'] = $this->tenantId;
                self::logValidationIssues($row);
            })
            ->mapAndTransform(
                'MODULE_TITLE',
                'title',
                static fn (string $moduleTitle): string => trim($moduleTitle)
            )
            ->mapAndTransform(
                'MODULE_CODE',
                'code',
                static fn (string $moduleCode): string => trim($moduleCode)
            )
            ->map('__TENANT_ID', 'tenant_id')
            ->mapAndTransform(
                'COURSE_CODE',
                'course_syllabus_id',
                fn (string $courseCode): int => self::resolveCourseSyllabusId($this->tenantId, $courseCode)
            )
            ->mapAndTransform(
                'SEMESTER',
                'academic_year_option_id',
                fn (string $semester, array $row): int => app(ResolveAcademicYearOptionFromImport::class)->resolve(
                    $semester,
                    self::tryResolveCourseSyllabusId($this->tenantId, (string) ($row['COURSE_CODE'] ?? '')),
                    $this->resolveInstitutionDepartmentId($row),
                    (string) ($row['LEVEL'] ?? ''),
                )
            )
            ->validate([
                'COURSE_CODE' => ['required', 'string'],
                'SEMESTER' => ['required', 'string'],
                'MODULE_TITLE' => ['required', 'string'],
                'MODULE_CODE' => ['required', 'string'],
            ]);

        if ($this->fromFilesystem) {
            return $config->fromSource(SourceType::FILESYSTEM, [
                'disk' => 'ingest',
                'path' => 'syllabus.xlsx',
            ]);
        }

        return $config;
    }

    private function resolveInstitutionDepartmentId(array $row): ?int
    {
        if ($this->institutionDepartmentId !== null) {
            return $this->institutionDepartmentId;
        }

        $department = trim((string) ($row['DEPARTMENT'] ?? ''));

        if ($department === '') {
            return null;
        }

        $institutionDepartment = InstitutionDepartment::query()
            ->where('tenant_id', $this->tenantId)
            ->whereHas('department', function ($query) use ($department): void {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($department)]);
            })
            ->first();

        return $institutionDepartment?->id;
    }

    private static function resolveCourseSyllabusId(int $tenantId, string $courseCode): int
    {
        $courseSyllabusId = self::tryResolveCourseSyllabusId($tenantId, $courseCode);

        if ($courseSyllabusId === null) {
            Log::warning('Syllabus module import lookup failed: course syllabus missing.', [
                'tenant_id' => $tenantId,
                'course_code' => $courseCode,
            ]);
            throw new RuntimeException("Course syllabus not found for COURSE_CODE '{$courseCode}'.");
        }

        return $courseSyllabusId;
    }

    private static function tryResolveCourseSyllabusId(int $tenantId, string $courseCode): ?int
    {
        $courseCode = trim($courseCode);

        if ($courseCode === '') {
            return null;
        }

        return CourseSyllabus::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $courseCode)
            ->value('id');
    }

    private static function logValidationIssues(array $row): void
    {
        $requiredFields = ['COURSE_CODE', 'SEMESTER', 'MODULE_TITLE', 'MODULE_CODE'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            $value = $row[$field] ?? null;

            if ($value === null || trim((string) $value) === '') {
                $missingFields[] = $field;
            }
        }

        if ($missingFields !== []) {
            Log::warning('Syllabus module import validation failed: missing required fields.', [
                'missing_fields' => $missingFields,
                'row' => $row,
            ]);
        }
    }
}
