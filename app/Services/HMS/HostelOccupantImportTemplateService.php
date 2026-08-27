<?php

declare(strict_types=1);

namespace App\Services\HMS;

use App\Importers\HMS\HostelOccupantImporter;
use App\Models\HMS\Hostel;

class HostelOccupantImportTemplateService
{
    /**
     * @return array{
     *     header: array{generatedAt: string, hostelName: string},
     *     rows: list<list<string|null>>,
     * }
     */
    public function assemble(Hostel $hostel): array
    {
        return [
            'header' => [
                'generatedAt' => now()->format('d M Y'),
                'hostelName' => $hostel->name,
            ],
            'rows' => [
                [null, null, null, 'No', $hostel->name, '0', null, 'A'],
            ],
        ];
    }

    public function downloadFileName(Hostel $hostel): string
    {
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $hostel->name));
        $slug = trim($slug, '-');

        return 'hostel-occupant-import-'.$slug.'-'.now()->format('Y-m-d-His').'.xlsx';
    }

    /**
     * @return list<string>
     */
    public function instructions(string $hostelName): array
    {
        return [
            'Fill in one row per occupant for '.$hostelName.'.',
            'Student Number is preferred. Use ID Number for Zimbabwean citizens and Passport Number for non-Zimbabwean students.',
            'Do not put a Zimbabwean national ID in the Passport Number column, or a passport in the ID Number column.',
            'Disability must be Yes or No.',
            'Hostel must match '.$hostelName.'.',
            'Floor must exist in this hostel. Use 0, G, or Ground for the ground floor.',
            'Room must match an existing room name on that floor.',
            'Section must exist in that room: A or B (C only for triple rooms).',
            'Everyone on this list is imported as paid. Submitting the import confirms hostel payments.',
        ];
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return HostelOccupantImporter::COLUMNS;
    }
}
