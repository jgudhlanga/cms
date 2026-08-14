<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Importers\Maintenance\SponsoredStudentImporter;

class SponsoredStudentImportTemplateService
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
                [null, null],
            ],
        ];
    }

    public function downloadFileName(): string
    {
        return 'sponsored-students-import-template-'.now()->format('Y-m-d-His').'.xlsx';
    }

    /**
     * @return list<string>
     */
    public function instructions(): array
    {
        return [
            'Fill in one row per sponsored student.',
            'Each row must include a Student Number. Matching is by student number only.',
            'Sponsor is the organisation or person sponsoring the student for this calendar year.',
            'Students are matched by student number against applications for the current calendar year.',
            'If a student already has a sponsor for the year, the import updates that record instead of creating a duplicate.',
            'Supported upload column aliases include Sponsor Name.',
        ];
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return SponsoredStudentImporter::COLUMNS;
    }
}
