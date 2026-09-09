<?php

declare(strict_types=1);

namespace App\Exports\Institution;

use App\Services\Institution\Reconciliation\EnrolmentVsClassListImportTemplateService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class EnrolmentVsClassListImportTemplateExport implements WithMultipleSheets
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
            new EnrolmentVsClassListImportTemplateDataSheetExport($this->data),
            new EnrolmentVsClassListImportTemplateInstructionsSheetExport($this->data),
        ];
    }
}

class EnrolmentVsClassListImportTemplateDataSheetExport implements FromArray, WithTitle
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
        $rows = $this->data['rows'] ?? [[null, null, null]];
        $templateService = app(EnrolmentVsClassListImportTemplateService::class);

        return [
            ['System Enrolment vs Physical Class List Template'],
            ['Department', $header['department'] ?? null],
            ['Generated', $header['generatedAt'] ?? null],
            [null],
            $templateService->columns(),
            ...$rows,
        ];
    }

    public function title(): string
    {
        return 'Class List';
    }
}

class EnrolmentVsClassListImportTemplateInstructionsSheetExport implements FromArray, WithTitle
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
        $templateService = app(EnrolmentVsClassListImportTemplateService::class);
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
