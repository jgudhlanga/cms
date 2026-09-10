<?php

declare(strict_types=1);

namespace App\Services\AcademicCalendars;

use App\Enums\AcademicCalendars\EnrolmentProgressionImportAction;
use App\Importers\AcademicCalendars\StudentNumberProgressionImporter;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\InstitutionDepartment;

class EnrolmentProgressionImportTemplateService
{
    /**
     * @return array{
     *     header: array{generatedAt: string, department: string, action: string, calendarYear: string},
     *     rows: list<list<string|null>>,
     * }
     */
    public function assemble(
        InstitutionDepartment $department,
        ClassConfig $classConfig,
        EnrolmentProgressionImportAction $action,
    ): array {
        $department->loadMissing('department');

        return [
            'header' => [
                'generatedAt' => now()->toDateTimeString(),
                'department' => (string) ($department->department?->name ?? ''),
                'action' => $action->label(),
                'calendarYear' => (string) $classConfig->calendar_year,
            ],
            'rows' => [
                [null],
            ],
        ];
    }

    public function downloadFileName(
        InstitutionDepartment $department,
        ClassConfig $classConfig,
        EnrolmentProgressionImportAction $action,
    ): string {
        $department->loadMissing('department');
        $slug = strtolower(preg_replace('/\s+/', '-', (string) ($department->department_code ?? 'department')) ?? 'department');

        return sprintf(
            'progression-%s-%s-%s-%s.xlsx',
            $action->value,
            $slug,
            $classConfig->calendar_year,
            now()->format('Y-m-d-His'),
        );
    }

    /**
     * @return list<string>
     */
    public function instructions(EnrolmentProgressionImportAction $action): array
    {
        $base = [
            'Fill in one student number per row under the Student Number column.',
            'Download this template, add the students who passed, then upload for preview.',
            'Only students found on classes for this class configuration can be processed.',
            'Ineligible rows are skipped (wrong phase, blocking status, already completed, or not found).',
        ];

        return match ($action) {
            EnrolmentProgressionImportAction::CompleteLevel => [
                ...$base,
                'Use this list for students assumed to have completed the level (last phase → Award).',
            ],
            EnrolmentProgressionImportAction::AdvancePhase => [
                ...$base,
                'Use this list for students who should continue to the next semester/phase within the same level.',
            ],
        };
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return StudentNumberProgressionImporter::COLUMNS;
    }
}
