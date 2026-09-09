<?php

declare(strict_types=1);

namespace App\Exports\Institution;

use App\Models\Institution\InstitutionDepartment;
use App\Services\Institution\Reconciliation\SemesterReconciliationImportTemplateService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class SemesterReconciliationImportTemplateExport implements WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly array $data,
        private readonly InstitutionDepartment $department,
    ) {}

    /**
     * @return array<int, FromArray&WithTitle>
     */
    public function sheets(): array
    {
        return [
            new SemesterReconciliationImportTemplateDataSheetExport($this->data),
            new SemesterReconciliationImportTemplateInstructionsSheetExport($this->data, $this->department),
        ];
    }
}

class SemesterReconciliationImportTemplateDataSheetExport implements FromArray, WithTitle
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data) {}

    /**
     * @return array<int, array<int|string|null>>
     */
    public function array(): array
    {
        /** @var array<string, mixed> $header */
        $header = $this->data['header'] ?? [];
        /** @var list<list<mixed>> $rows */
        $rows = $this->data['rows'] ?? [[null, null, null, null]];
        $templateService = app(SemesterReconciliationImportTemplateService::class);

        return [
            ['Semester Reconciliation Template'],
            ['Department', $header['department'] ?? null],
            ['Generated', $header['generatedAt'] ?? null],
            [null],
            $templateService->columns(),
            ...$rows,
        ];
    }

    public function title(): string
    {
        return 'Semester Progress';
    }
}

class SemesterReconciliationImportTemplateInstructionsSheetExport implements FromArray, WithTitle
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly array $data,
        private readonly InstitutionDepartment $department,
    ) {}

    /**
     * @return array<int, array<int|string|null>>
     */
    public function array(): array
    {
        $templateService = app(SemesterReconciliationImportTemplateService::class);
        $instructions = array_map(
            static fn (string $instruction): array => [$instruction],
            $templateService->instructions($this->department),
        );

        return [
            ['Instructions'],
            [null],
            ...$instructions,
        ];
    }

    public function title(): string
    {
        return 'Instructions';
    }
}
