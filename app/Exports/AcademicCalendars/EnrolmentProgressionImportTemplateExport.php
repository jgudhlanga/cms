<?php

declare(strict_types=1);

namespace App\Exports\AcademicCalendars;

use App\Enums\AcademicCalendars\EnrolmentProgressionImportAction;
use App\Models\Institution\InstitutionDepartment;
use App\Services\AcademicCalendars\EnrolmentProgressionImportTemplateService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class EnrolmentProgressionImportTemplateExport implements WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly array $data,
        private readonly EnrolmentProgressionImportAction $action,
        private readonly InstitutionDepartment $department,
    ) {}

    /**
     * @return array<int, FromArray&WithTitle>
     */
    public function sheets(): array
    {
        return [
            new EnrolmentProgressionImportTemplateDataSheetExport($this->data),
            new EnrolmentProgressionImportTemplateInstructionsSheetExport($this->action, $this->department),
        ];
    }
}

class EnrolmentProgressionImportTemplateDataSheetExport implements FromArray, WithTitle
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
        $rows = $this->data['rows'] ?? [[null]];
        $templateService = app(EnrolmentProgressionImportTemplateService::class);

        return [
            ['Enrolment Progression Import Template'],
            ['Department', $header['department'] ?? null],
            ['Action', $header['action'] ?? null],
            ['Calendar Year', $header['calendarYear'] ?? null],
            ['Generated', $header['generatedAt'] ?? null],
            [null],
            $templateService->columns(),
            ...$rows,
        ];
    }

    public function title(): string
    {
        return 'Students';
    }
}

class EnrolmentProgressionImportTemplateInstructionsSheetExport implements FromArray, WithTitle
{
    public function __construct(
        private readonly EnrolmentProgressionImportAction $action,
        private readonly InstitutionDepartment $department,
    ) {}

    /**
     * @return array<int, array<int|string|null>>
     */
    public function array(): array
    {
        $templateService = app(EnrolmentProgressionImportTemplateService::class);
        $instructions = array_map(
            static fn (string $instruction): array => [$instruction],
            $templateService->instructions($this->action),
        );

        $this->department->loadMissing('department');

        return [
            ['Instructions'],
            ['Department', (string) ($this->department->department?->name ?? '')],
            [null],
            ...$instructions,
        ];
    }

    public function title(): string
    {
        return 'Instructions';
    }
}
