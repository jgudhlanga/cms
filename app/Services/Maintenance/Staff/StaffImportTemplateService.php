<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Staff;

use App\Importers\Maintenance\StaffImporter;

class StaffImportTemplateService
{
    public function __construct(private readonly StaffImportLookups $lookups) {}

    /**
     * @return array{
     *     header: array{generatedAt: string, tenantId: int},
     *     lookups: array<string, list<string>>,
     *     exampleRow: array<string, string|null>
     * }
     */
    public function assemble(int $tenantId): array
    {
        return [
            'header' => [
                'generatedAt' => now()->format('d M Y'),
                'tenantId' => $tenantId,
            ],
            'lookups' => $this->lookups->labelsForTemplate($tenantId),
            'exampleRow' => array_fill_keys(StaffImporter::COLUMNS, null),
        ];
    }

    public function downloadFileName(): string
    {
        return sprintf('staff-import-template-%s.xlsx', time());
    }
}
