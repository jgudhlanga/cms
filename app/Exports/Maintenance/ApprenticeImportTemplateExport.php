<?php

declare(strict_types=1);

namespace App\Exports\Maintenance;

use App\Services\Maintenance\Students\ApprenticeImportTemplateService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ApprenticeImportTemplateExport implements WithMultipleSheets
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
            new ApprenticeImportTemplateDataSheetExport($this->data),
            new ApprenticeImportTemplateInstructionsSheetExport($this->data),
        ];
    }
}

class ApprenticeImportTemplateDataSheetExport implements FromArray, WithTitle
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
        $templateService = app(ApprenticeImportTemplateService::class);

        return [
            ['Apprentice Import Template'],
            ['Generated', $header['generatedAt'] ?? null],
            [null],
            $templateService->columns(),
            ...$rows,
        ];
    }

    public function title(): string
    {
        return 'Apprentices';
    }
}

class ApprenticeImportTemplateInstructionsSheetExport implements FromArray, WithTitle
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
        $templateService = app(ApprenticeImportTemplateService::class);
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
