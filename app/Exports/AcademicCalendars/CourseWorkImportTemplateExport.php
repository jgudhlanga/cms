<?php

namespace App\Exports\AcademicCalendars;

use App\Importers\AcademicCalendars\CourseWorkMarkImporter;
use App\Support\AcademicCalendars\CourseWorkTemplateSignature;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Course work import template. Only open mark cells are editable: every other cell is locked behind
 * a random, never-stored sheet password, the workbook structure is locked, and the template context
 * is signed in a very-hidden sheet so uploads can be verified.
 */
class CourseWorkImportTemplateExport implements WithEvents, WithMultipleSheets
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
        $sheets = [
            new CourseWorkImportTemplateMarksSheetExport($this->data),
            new CourseWorkImportTemplateInstructionsSheetExport($this->data),
        ];

        $signature = $this->data['signature'] ?? null;

        if (is_array($signature) && isset($signature['payload'], $signature['signature'])) {
            $sheets[] = new CourseWorkImportTemplateMetaSheetExport($signature);
        }

        return $sheets;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event): void {
                $spreadsheet = $event->writer->getDelegate();
                $spreadsheet->getSecurity()
                    ->setLockStructure(true)
                    ->setWorkbookPassword(Str::random(40));
                $spreadsheet->setActiveSheetIndex(0);
            },
        ];
    }

    public static function protect(Worksheet $sheet): void
    {
        $protection = $sheet->getProtection();
        $protection->setPassword(Str::random(40));
        $protection->setSheet(true);
        $protection->setObjects(true);
        $protection->setScenarios(true);
        $protection->setFormatCells(true);
        $protection->setFormatColumns(true);
        $protection->setFormatRows(true);
        $protection->setInsertColumns(true);
        $protection->setInsertRows(true);
        $protection->setInsertHyperlinks(true);
        $protection->setDeleteColumns(true);
        $protection->setDeleteRows(true);
        $protection->setSort(true);
        $protection->setAutoFilter(true);
        $protection->setPivotTables(true);
    }
}

class CourseWorkImportTemplateMarksSheetExport implements FromArray, WithEvents, WithTitle
{
    public const int FIXED_COLUMN_COUNT = 4;

    public const int HEADER_ROW = 6;

    public const int ASSESSMENT_ID_ROW = 7;

    public const int DATA_START_ROW = 8;

    /** Mark-only templates have no assessment id row, so students start straight after the header. */
    public const int MARK_ONLY_DATA_START_ROW = 7;

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
        /** @var list<array{id: int, name: string, weightPercent: int|null}> $assessmentTypes */
        $assessmentTypes = $this->data['assessmentTypes'] ?? [];
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->data['rows'] ?? [];
        $layout = (string) ($this->data['layout'] ?? 'wide');

        if ($layout === 'mark_only') {
            $columnHeaders = CourseWorkMarkImporter::markOnlyHeaderColumns();
            $output = [
                ['Course Work Import Template (Mark Only)'],
                ['Module', $header['moduleCode'] ?? null, $header['moduleTitle'] ?? null],
                ['Course', $header['course'] ?? null, 'Level', $header['level'] ?? null],
                ['Mode', $header['modeOfStudy'] ?? null, 'Year', $header['calendarYear'] ?? null],
                ['Generated', $header['generatedAt'] ?? null],
                $columnHeaders,
            ];

            foreach ($rows as $row) {
                $output[] = [
                    $row['studentEnrolmentId'] ?? null,
                    $row['studentNumber'] ?? null,
                    $row['studentName'] ?? null,
                    $row['className'] ?? null,
                    $row['mark'] ?? null,
                    $row['remark'] ?? null,
                ];
            }

            return $output;
        }

        $columnHeaders = [
            'STUDENT_ENROLMENT_ID',
            'STUDENT_NUMBER',
            'STUDENT_NAME',
            'CLASS_NAME',
        ];

        foreach ($assessmentTypes as $type) {
            $weight = $type['weightPercent'] ?? '';
            $columnHeaders[] = sprintf('%s (%s%%)', $type['name'], $weight);
        }

        $assessmentIdRow = [null, null, null, null];
        foreach ($assessmentTypes as $type) {
            $assessmentIdRow[] = $type['id'];
        }

        $output = [
            ['Course Work Import Template'],
            ['Module', $header['moduleCode'] ?? null, $header['moduleTitle'] ?? null],
            ['Course', $header['course'] ?? null, 'Level', $header['level'] ?? null],
            ['Mode', $header['modeOfStudy'] ?? null, 'Year', $header['calendarYear'] ?? null],
            ['Generated', $header['generatedAt'] ?? null],
            $columnHeaders,
            $assessmentIdRow,
        ];

        foreach ($rows as $row) {
            /** @var array<int, int|null> $marks */
            $marks = $row['marks'] ?? [];
            $line = [
                $row['studentEnrolmentId'] ?? null,
                $row['studentNumber'] ?? null,
                $row['studentName'] ?? null,
                $row['className'] ?? null,
            ];

            foreach ($assessmentTypes as $type) {
                $line[] = $marks[(int) $type['id']] ?? null;
            }

            $output[] = $line;
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

                if ((string) ($this->data['layout'] ?? 'wide') === 'mark_only') {
                    $this->lockMarkOnlySheet($sheet);
                } else {
                    $this->lockWideSheet($sheet);
                }

                CourseWorkImportTemplateExport::protect($sheet);
            },
        ];
    }

    public function title(): string
    {
        return 'Marks';
    }

    private function lockWideSheet(Worksheet $sheet): void
    {
        /** @var list<array{id: int, name: string, weightPercent: int|null}> $assessmentTypes */
        $assessmentTypes = $this->data['assessmentTypes'] ?? [];
        $lastColumnIndex = self::FIXED_COLUMN_COUNT + max(count($assessmentTypes), 1);
        $lastDataRow = $this->lastDataRow(self::DATA_START_ROW);
        $editableTypeIds = $this->data['editableAssessmentTypeIds'] ?? null;

        $this->lockRange($sheet, 1, $lastColumnIndex, $lastDataRow);

        foreach (array_values($assessmentTypes) as $position => $type) {
            $columnLetter = Coordinate::stringFromColumnIndex(self::FIXED_COLUMN_COUNT + $position + 1);
            $range = $columnLetter.self::DATA_START_ROW.':'.$columnLetter.$lastDataRow;
            $isEditable = ! is_array($editableTypeIds) || in_array((int) $type['id'], array_map('intval', $editableTypeIds), true);

            if ($isEditable) {
                $sheet->getStyle($range)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
                $this->validateMarks($sheet, $columnLetter, self::DATA_START_ROW, $lastDataRow);

                continue;
            }

            $sheet->getStyle($columnLetter.self::HEADER_ROW.':'.$columnLetter.$lastDataRow)
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFE5E7EB');
            $sheet->getComment($columnLetter.self::HEADER_ROW)
                ->getText()
                ->createTextRun($this->closedMessageFor((int) $type['id']));
        }

        // The id row maps columns to assessment types; it stays in the file (and is signed) but out of sight.
        $sheet->getRowDimension(self::ASSESSMENT_ID_ROW)->setVisible(false);
    }

    private function lockMarkOnlySheet(Worksheet $sheet): void
    {
        $columns = CourseWorkMarkImporter::markOnlyHeaderColumns();
        $lastDataRow = $this->lastDataRow(self::MARK_ONLY_DATA_START_ROW);
        $markColumnIndex = (int) array_search('MARK', $columns, true) + 1;
        $markColumnLetter = Coordinate::stringFromColumnIndex($markColumnIndex);

        $this->lockRange($sheet, 1, count($columns), $lastDataRow);

        if (! ($this->data['markEditable'] ?? true)) {
            $sheet->getStyle($markColumnLetter.self::HEADER_ROW.':'.$markColumnLetter.$lastDataRow)
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFE5E7EB');

            return;
        }

        $sheet->getStyle($markColumnLetter.self::MARK_ONLY_DATA_START_ROW.':'.$markColumnLetter.$lastDataRow)
            ->getProtection()
            ->setLocked(Protection::PROTECTION_UNPROTECTED);
        $this->validateMarks($sheet, $markColumnLetter, self::MARK_ONLY_DATA_START_ROW, $lastDataRow);
    }

    private function lockRange(Worksheet $sheet, int $firstColumnIndex, int $lastColumnIndex, int $lastRow): void
    {
        $sheet->getStyle(
            Coordinate::stringFromColumnIndex($firstColumnIndex).'1:'.Coordinate::stringFromColumnIndex($lastColumnIndex).$lastRow
        )->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
    }

    private function validateMarks(Worksheet $sheet, string $columnLetter, int $firstRow, int $lastRow): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_WHOLE);
        $validation->setOperator(DataValidation::OPERATOR_BETWEEN);
        $validation->setFormula1('0');
        $validation->setFormula2('100');
        $validation->setAllowBlank(true);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle(__('academic_calendar.course_work_template_invalid_mark_title'));
        $validation->setError(__('academic_calendar.course_work_mark_invalid'));
        $validation->setShowInputMessage(true);
        $validation->setPromptTitle(__('academic_calendar.course_work_template_invalid_mark_title'));
        $validation->setPrompt(__('academic_calendar.course_work_template_mark_prompt'));

        for ($row = $firstRow; $row <= $lastRow; $row++) {
            $sheet->getCell($columnLetter.$row)->setDataValidation(clone $validation);
        }
    }

    private function lastDataRow(int $dataStartRow): int
    {
        $rows = $this->data['rows'] ?? [];

        return max($dataStartRow, $dataStartRow + count($rows) - 1);
    }

    private function closedMessageFor(int $assessmentTypeId): string
    {
        foreach ($this->data['closedAssessments'] ?? [] as $closed) {
            if ((int) ($closed['id'] ?? 0) === $assessmentTypeId) {
                return (string) $closed['message'];
            }
        }

        return (string) ($this->data['readOnlyMessage'] ?? __('academic_calendar.course_work_import_instruction_locked'));
    }
}

class CourseWorkImportTemplateInstructionsSheetExport implements FromArray, WithEvents, WithTitle
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data = []) {}

    /**
     * @return array<int, array<int|string|null>>
     */
    public function array(): array
    {
        $lines = [
            ['Instructions'],
            [__('academic_calendar.course_work_import_instruction_locked')],
            [__('academic_calendar.course_work_import_instruction_do_not_edit_ids')],
            [__('academic_calendar.course_work_import_instruction_marks')],
            [__('academic_calendar.course_work_import_instruction_skip')],
            [__('academic_calendar.course_work_import_instruction_signed')],
        ];

        $closedNames = collect($this->data['closedAssessments'] ?? [])->pluck('name')->filter()->implode(', ');

        if ($closedNames !== '') {
            $lines[] = [__('academic_calendar.course_work_import_instruction_closed', ['assessments' => $closedNames])];
        }

        if (filled($this->data['readOnlyMessage'] ?? null)) {
            $lines[] = [(string) $this->data['readOnlyMessage']];
        }

        return $lines;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                CourseWorkImportTemplateExport::protect($event->sheet->getDelegate());
            },
        ];
    }

    public function title(): string
    {
        return 'Instructions';
    }
}

class CourseWorkImportTemplateMetaSheetExport implements FromArray, WithEvents, WithTitle
{
    /**
     * @param  array{payload: string, signature: string}  $signature
     */
    public function __construct(private readonly array $signature) {}

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            [CourseWorkTemplateSignature::PAYLOAD_PREFIX.$this->signature['payload']],
            [CourseWorkTemplateSignature::SIGNATURE_PREFIX.$this->signature['signature']],
        ];
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
                CourseWorkImportTemplateExport::protect($sheet);
            },
        ];
    }

    public function title(): string
    {
        return CourseWorkTemplateSignature::META_SHEET_TITLE;
    }
}
