<?php

declare(strict_types=1);

namespace App\Exports\Maintenance;

use App\Services\Maintenance\Students\SponsoredStudentImportTemplateService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class SponsoredStudentImportTemplateExport implements WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data) {}

    /**
     * @return array<int, FromArray&WithTitle>
     */
    public function sheets(): array
    {
        return [
            new SponsoredStudentImportTemplateDataSheetExport($this->data),
            new SponsoredStudentImportTemplateInstructionsSheetExport($this->data),
        ];
    }
}

class SponsoredStudentImportTemplateDataSheetExport implements FromArray, WithTitle
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
        $rows = $this->data['rows'] ?? [[null, null]];
        $templateService = app(SponsoredStudentImportTemplateService::class);

        return [
            ['Sponsored Students Import Template'],
            ['Generated', $header['generatedAt'] ?? null],
            [null],
            $templateService->columns(),
            ...$rows,
        ];
    }

    public function title(): string
    {
        return 'Sponsored Students';
    }
}

class SponsoredStudentImportTemplateInstructionsSheetExport implements FromArray, WithTitle
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
        $templateService = app(SponsoredStudentImportTemplateService::class);
        $instructions = array_map(
            static fn (string $instruction): array => [$instruction],
            $templateService->instructions(),
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
