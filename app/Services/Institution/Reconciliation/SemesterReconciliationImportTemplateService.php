<?php

declare(strict_types=1);

namespace App\Services\Institution\Reconciliation;

use App\Importers\Institution\SemesterReconciliationImporter;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ProgrammeSemester;
use App\Support\Institution\ProgrammeSemesterNameFormatter;

class SemesterReconciliationImportTemplateService
{
    /**
     * @return array{
     *     header: array{generatedAt: string, department: string},
     *     rows: list<list<string|null>>,
     *     phaseExamples: list<string>,
     * }
     */
    public function assemble(InstitutionDepartment $department): array
    {
        $department->loadMissing('department');

        return [
            'header' => [
                'generatedAt' => now()->toDateTimeString(),
                'department' => (string) ($department->department?->name ?? ''),
            ],
            'rows' => [
                [null, null, null, null],
            ],
            'phaseExamples' => $this->phaseExamples($department),
        ];
    }

    public function downloadFileName(InstitutionDepartment $department): string
    {
        $department->loadMissing('department');
        $slug = strtolower(preg_replace('/\s+/', '-', (string) ($department->department_code ?? 'department')) ?? 'department');

        return "semester-reconciliation-{$slug}-".now()->format('Y-m-d-His').'.xlsx';
    }

    /**
     * @return list<string>
     */
    public function instructions(InstitutionDepartment $department): array
    {
        $examples = $this->phaseExamples($department);
        $exampleLine = $examples === []
            ? 'No programme phases are configured for this department yet.'
            : 'Valid phase examples for this department: '.implode(', ', array_slice($examples, 0, 20));

        return [
            'Fill in one row per enrolled student with their current programme phase from the department file.',
            'Student Number, Level, Course, and Programme Phase are required.',
            'Programme Phase must match a programme semester name for the student\'s offering (e.g. Year 1 Sem 2, Year 1 Term 3, Year 1 ABMA 1, Year 2 Attachment 1).',
            'Short forms such as Y1 S2 are also accepted.',
            $exampleLine,
            'Students not enrolled in this department for the selected year should be elevated via Enrolment vs Physical Class List first.',
        ];
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return SemesterReconciliationImporter::COLUMNS;
    }

    /**
     * @return list<string>
     */
    private function phaseExamples(InstitutionDepartment $department): array
    {
        $phases = ProgrammeSemester::query()
            ->whereHas('departmentLevelCourse.departmentLevel', function ($query) use ($department): void {
                $query->where('institution_department_id', $department->id);
            })
            ->orderBy('position')
            ->limit(40)
            ->get(['name']);

        return $phases
            ->map(function (ProgrammeSemester $phase): array {
                return array_filter([
                    (string) $phase->name,
                    ProgrammeSemesterNameFormatter::shortName($phase->name),
                ]);
            })
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }
}
