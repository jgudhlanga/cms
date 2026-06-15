<?php

declare(strict_types=1);

namespace App\Exports\Maintenance;

use App\Importers\Maintenance\StaffImporter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class StaffImportTemplateExport implements WithMultipleSheets
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
            new StaffImportTemplateStaffSheetExport($this->data),
            new StaffImportTemplateLookupsSheetExport($this->data),
            new StaffImportTemplateInstructionsSheetExport,
        ];
    }
}

class StaffImportTemplateStaffSheetExport implements FromArray, WithEvents, WithTitle
{
    public const int HEADER_ROW = 5;

    public const int DATA_START_ROW = 6;

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
        $rows = $this->data['rows'] ?? [array_fill(0, count(StaffImporter::COLUMNS), null)];

        $output = [
            ['Staff Import Template'],
            ['Generated', $header['generatedAt'] ?? null],
            ['Tenant ID', $header['tenantId'] ?? null],
            [null],
            StaffImporter::COLUMNS,
        ];

        foreach ($rows as $row) {
            $output[] = $row;
        }

        return $output;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumnLetter = Coordinate::stringFromColumnIndex(count(StaffImporter::COLUMNS));
                /** @var list<list<mixed>> $rows */
                $rows = $this->data['rows'] ?? [array_fill(0, count(StaffImporter::COLUMNS), null)];
                $lastDataRow = self::DATA_START_ROW + count($rows) - 1;

                if ($lastDataRow < self::DATA_START_ROW) {
                    $lastDataRow = self::DATA_START_ROW;
                }

                $sheet->getStyle('A1:'.$lastColumnLetter.$lastDataRow)
                    ->getProtection()
                    ->setLocked(Protection::PROTECTION_PROTECTED);

                $sheet->getStyle('A'.self::DATA_START_ROW.':'.$lastColumnLetter.self::DATA_START_ROW)
                    ->getProtection()
                    ->setLocked(Protection::PROTECTION_UNPROTECTED);

                $sheet->getProtection()->setSheet(true);
            },
        ];
    }

    public function title(): string
    {
        return 'Staff';
    }
}

class StaffImportTemplateLookupsSheetExport implements FromArray, WithTitle
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
        /** @var array<string, list<string>> $lookups */
        $lookups = $this->data['lookups'] ?? [];

        $rows = [['Lookups']];

        foreach ($lookups as $label => $values) {
            $rows[] = [strtoupper(str_replace('_', ' ', (string) $label))];
            foreach ($values as $value) {
                $rows[] = [$value];
            }
            $rows[] = [null];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Lookups';
    }
}

class StaffImportTemplateInstructionsSheetExport implements FromArray, WithTitle
{
    /**
     * @return array<int, array<int|string|null>>
     */
    public function array(): array
    {
        return [
            ['Instructions'],
            [__('trans.maintenance_staff_import_instruction_required')],
            [__('trans.maintenance_staff_import_instruction_department')],
            [__('trans.maintenance_staff_import_instruction_date')],
            [__('trans.maintenance_staff_import_instruction_roles')],
            [__('trans.maintenance_staff_import_instruction_upsert')],
        ];
    }

    public function title(): string
    {
        return 'Instructions';
    }
}
