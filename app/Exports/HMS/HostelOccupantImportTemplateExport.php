<?php

declare(strict_types=1);

namespace App\Exports\HMS;

use App\Services\HMS\HostelOccupantImportTemplateService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class HostelOccupantImportTemplateExport implements WithMultipleSheets
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
            new HostelOccupantImportTemplateDataSheetExport($this->data),
            new HostelOccupantImportTemplateInstructionsSheetExport($this->data),
        ];
    }
}

class HostelOccupantImportTemplateDataSheetExport implements FromArray, WithTitle
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
        $rows = $this->data['rows'] ?? [[null, null, null, null, null, null, null, null]];
        $templateService = app(HostelOccupantImportTemplateService::class);

        return [
            ['Hostel Occupant Import Template'],
            ['Generated', $header['generatedAt'] ?? null],
            ['Hostel', $header['hostelName'] ?? null],
            [null],
            $templateService->columns(),
            ...$rows,
        ];
    }

    public function title(): string
    {
        return 'Occupants';
    }
}

class HostelOccupantImportTemplateInstructionsSheetExport implements FromArray, WithTitle
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
        $templateService = app(HostelOccupantImportTemplateService::class);
        /** @var array<string, mixed> $header */
        $header = $this->data['header'] ?? [];
        $instructions = array_map(
            static fn (string $instruction): array => [$instruction],
            $templateService->instructions((string) ($header['hostelName'] ?? '')),
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
