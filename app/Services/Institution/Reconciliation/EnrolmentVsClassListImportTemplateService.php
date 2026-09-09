<?php

declare(strict_types=1);

namespace App\Services\Institution\Reconciliation;

use App\Importers\Institution\EnrolmentVsClassListImporter;
use App\Models\Institution\InstitutionDepartment;

class EnrolmentVsClassListImportTemplateService
{
    /**
     * @return array{
     *     header: array{generatedAt: string, department: string},
     *     rows: list<list<string|null>>,
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
                [null, null, null],
            ],
        ];
    }

    public function downloadFileName(InstitutionDepartment $department): string
    {
        $department->loadMissing('department');
        $slug = strtolower(preg_replace('/\s+/', '-', (string) ($department->department_code ?? 'department')) ?? 'department');

        return "enrolment-vs-class-list-{$slug}-".now()->format('Y-m-d-His').'.xlsx';
    }

    /**
     * @return list<string>
     */
    public function instructions(): array
    {
        return [
            'Fill in one row per student on the physical class list.',
            'Student Number is required and is used to match students on the system.',
            'Level and Course must match offerings linked to this department.',
            'Supported course aliases include Programme and Program.',
            'Use the preview to compare system enrolments with the physical list before elevating students.',
            'Students already enrolled elsewhere (other department, level, or course) are highlighted and cannot be elevated here.',
        ];
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return EnrolmentVsClassListImporter::COLUMNS;
    }
}
