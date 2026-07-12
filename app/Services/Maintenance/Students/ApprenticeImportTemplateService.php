<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Importers\Maintenance\ApprenticeImporter;

class ApprenticeImportTemplateService
{
    /**
     * @return array{
     *     header: array{generatedAt: string},
     *     rows: list<list<string|null>>,
     * }
     */
    public function assemble(): array
    {
        return [
            'header' => [
                'generatedAt' => now()->toDateTimeString(),
            ],
            'rows' => [
                [null, null, null, null],
            ],
        ];
    }

    public function downloadFileName(): string
    {
        return 'apprentice-import-template-'.now()->format('Y-m-d-His').'.xlsx';
    }

    /**
     * @return list<string>
     */
    public function instructions(): array
    {
        return [
            'Fill in one row per apprentice student.',
            'Each row must include either an ID Number or a Student Number.',
            'Apprentice Number and Employer are optional but recommended.',
            'Students are matched against enrolments in the selected department and calendar year.',
            'Supported upload column aliases include National ID Number, Number, Application Number, and Company.',
        ];
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return ApprenticeImporter::COLUMNS;
    }
}
