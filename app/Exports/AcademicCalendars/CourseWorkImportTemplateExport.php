<?php

namespace App\Exports\AcademicCalendars;

use App\Importers\AcademicCalendars\CourseWorkMarkImporter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class CourseWorkImportTemplateExport implements WithMultipleSheets
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
            new CourseWorkImportTemplateMarksSheetExport($this->data),
            new CourseWorkImportTemplateInstructionsSheetExport,
        ];
    }
}

class CourseWorkImportTemplateMarksSheetExport implements FromArray, WithEvents, WithTitle
{
    public const int FIXED_COLUMN_COUNT = 4;

    public const int HEADER_ROW = 6;

    public const int ASSESSMENT_ID_ROW = 7;

    public const int DATA_START_ROW = 8;

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
                /** @var list<array{id: int, name: string, weightPercent: int|null}> $assessmentTypes */
                $assessmentTypes = $this->data['assessmentTypes'] ?? [];
                /** @var list<array<string, mixed>> $rows */
                $rows = $this->data['rows'] ?? [];

                $assessmentCount = count($assessmentTypes);
                $lastColumnIndex = self::FIXED_COLUMN_COUNT + $assessmentCount;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($lastColumnIndex);
                $lastDataRow = self::DATA_START_ROW + count($rows) - 1;

                if ($lastDataRow < self::DATA_START_ROW) {
                    $lastDataRow = self::DATA_START_ROW;
                }

                $sheet->getStyle('A1:'.$lastColumnLetter.$lastDataRow)
                    ->getProtection()
                    ->setLocked(Protection::PROTECTION_PROTECTED);

                for ($columnIndex = self::FIXED_COLUMN_COUNT + 1; $columnIndex <= $lastColumnIndex; $columnIndex++) {
                    $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
                    $sheet->getStyle($columnLetter.self::DATA_START_ROW.':'.$columnLetter.$lastDataRow)
                        ->getProtection()
                        ->setLocked(Protection::PROTECTION_UNPROTECTED);
                }

                $sheet->getProtection()->setSheet(true);
            },
        ];
    }

    public function title(): string
    {
        return 'Marks';
    }
}

class CourseWorkImportTemplateInstructionsSheetExport implements FromArray, WithTitle
{
    /**
     * @return array<int, array<int|string|null>>
     */
    public function array(): array
    {
        return [
            ['Instructions'],
            [__('academic_calendar.course_work_import_instruction_do_not_edit_ids')],
            [__('academic_calendar.course_work_import_instruction_marks')],
            [__('academic_calendar.course_work_import_instruction_skip')],
        ];
    }

    public function title(): string
    {
        return 'Instructions';
    }
}
