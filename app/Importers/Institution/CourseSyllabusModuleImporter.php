<?php

namespace App\Importers\Institution;

use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use Illuminate\Support\Facades\Log;
use LaravelIngest\Contracts\IngestDefinition;
use LaravelIngest\Enums\DuplicateStrategy;
use LaravelIngest\Enums\SourceType;
use LaravelIngest\IngestConfig;
use RuntimeException;

class CourseSyllabusModuleImporter implements IngestDefinition
{
    private const int TENANT_ID = 1;

    public function getConfig(): IngestConfig
    {
        return IngestConfig::for(CourseSyllabusModule::class)
            ->fromSource(SourceType::FILESYSTEM, [
                'disk' => 'ingest',
                'path' => 'syllabus.xlsx',
            ])
            ->keyedBy('MODULE_CODE')
            ->onDuplicate(DuplicateStrategy::SKIP)
            ->beforeRow(function (array &$row): void {
                $row['__TENANT_ID'] = self::TENANT_ID;
                self::logValidationIssues($row);
            })
            ->map('MODULE_TITLE', 'title')
            ->map('MODULE_CODE', 'code')
            ->map('__TENANT_ID', 'tenant_id')
            ->mapAndTransform(
                'COURSE_CODE',
                'course_syllabus_id',
                static fn (string $courseCode): int => self::resolveCourseSyllabusId($courseCode)
            )
            ->validate([
                'COURSE_CODE' => ['required', 'string'],
                'MODULE_TITLE' => ['required', 'string'],
                'MODULE_CODE' => ['required', 'string'],
            ]);
    }

    private static function resolveCourseSyllabusId(string $courseCode): int
    {
        $courseSyllabus = CourseSyllabus::query()
            ->where('tenant_id', self::TENANT_ID)
            ->where('code', trim($courseCode))
            ->first();

        if ($courseSyllabus === null) {
            Log::warning('Syllabus module import lookup failed: course syllabus missing.', [
                'tenant_id' => self::TENANT_ID,
                'course_code' => $courseCode,
            ]);
            throw new RuntimeException("Course syllabus not found for COURSE_CODE '{$courseCode}'.");
        }

        return $courseSyllabus->id;
    }

    private static function logValidationIssues(array $row): void
    {
        $requiredFields = ['COURSE_CODE', 'MODULE_TITLE', 'MODULE_CODE'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            $value = $row[$field] ?? null;

            if ($value === null || trim((string) $value) === '') {
                $missingFields[] = $field;
            }
        }

        if ($missingFields !== []) {
            Log::warning('Syllabus module import validation failed: missing required fields.', [
                'tenant_id' => self::TENANT_ID,
                'missing_fields' => $missingFields,
                'row' => $row,
            ]);
        }
    }
}
