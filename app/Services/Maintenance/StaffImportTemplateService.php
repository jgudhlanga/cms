<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

use App\Importers\Maintenance\StaffImporter;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;

class StaffImportTemplateService
{
    /**
     * @return array{
     *     header: array{generatedAt: string, tenantId: int},
     *     lookups: array<string, list<string>>,
     *     exampleRow: array<string, string|null>
     * }
     */
    public function assemble(int $tenantId): array
    {
        $departments = InstitutionDepartment::query()
            ->where('tenant_id', $tenantId)
            ->with('department')
            ->get()
            ->map(fn (InstitutionDepartment $dept): string => (string) $dept->department?->name)
            ->filter()
            ->sort()
            ->values()
            ->all();

        return [
            'header' => [
                'generatedAt' => now()->format('d M Y'),
                'tenantId' => $tenantId,
            ],
            'lookups' => [
                'titles' => Title::query()->orderBy('name')->pluck('name')->all(),
                'genders' => Gender::query()->orderBy('title')->pluck('title')->all(),
                'maritalStatuses' => MaritalStatus::query()->orderBy('title')->pluck('title')->all(),
                'employmentTypes' => EmploymentType::query()->orderBy('name')->pluck('name')->all(),
                'departments' => $departments,
                'roles' => StaffImporter::ALLOWED_ROLE_SLUGS,
            ],
            'exampleRow' => array_fill_keys(StaffImporter::COLUMNS, null),
        ];
    }

    public function downloadFileName(): string
    {
        return sprintf('staff-import-template-%s.xlsx', time());
    }
}
