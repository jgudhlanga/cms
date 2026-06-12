<?php

declare(strict_types=1);

namespace App\Exports\Institution;

use App\Importers\Institution\CourseSyllabusImporter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class CourseSyllabusImportTemplateExport implements WithMultipleSheets
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
            new CourseSyllabusImportTemplateDataSheetExport($this->data),
            new CourseSyllabusImportTemplateLookupsSheetExport($this->data),
            new CourseSyllabusImportTemplateInstructionsSheetExport,
        ];
    }
}

class CourseSyllabusImportTemplateDataSheetExport implements FromArray, WithEvents, WithTitle
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
        $rows = $this->data['rows'] ?? [array_fill(0, count(CourseSyllabusImporter::WEB_COLUMNS), null)];

        $output = [
            [__('syllabus.import_template_title')],
            [__('syllabus.import_template_generated'), $header['generatedAt'] ?? null],
            [__('syllabus.import_template_department'), $header['departmentName'] ?? null],
            [null],
            CourseSyllabusImporter::WEB_COLUMNS,
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
                $lastColumnLetter = Coordinate::stringFromColumnIndex(count(CourseSyllabusImporter::WEB_COLUMNS));
                /** @var list<list<mixed>> $rows */
                $rows = $this->data['rows'] ?? [array_fill(0, count(CourseSyllabusImporter::WEB_COLUMNS), null)];
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
        return 'Syllabus';
    }
}

class CourseSyllabusImportTemplateLookupsSheetExport implements FromArray, WithTitle
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

        $rows = [[__('syllabus.import_template_lookups')]];

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

class CourseSyllabusImportTemplateInstructionsSheetExport implements FromArray, WithTitle
{
    /**
     * @return array<int, array<int|string|null>>
     */
    public function array(): array
    {
        return [
            [__('syllabus.import_template_instructions')],
            [__('syllabus.import_instruction_required')],
            [__('syllabus.import_instruction_course_code')],
            [__('syllabus.import_instruction_semester')],
            [__('syllabus.import_instruction_modules')],
            [__('syllabus.import_instruction_duplicates')],
            [__('syllabus.import_instruction_lookups')],
        ];
    }

    public function title(): string
    {
        return 'Instructions';
    }
}
