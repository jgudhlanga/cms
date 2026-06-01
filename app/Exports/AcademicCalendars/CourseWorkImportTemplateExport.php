<?php

namespace App\Exports\AcademicCalendars;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

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

class CourseWorkImportTemplateMarksSheetExport implements FromArray, WithTitle
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
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->data['rows'] ?? [];

        $output = [
            ['Course Work Import Template'],
            ['Module', $header['moduleCode'] ?? null, $header['moduleTitle'] ?? null],
            ['Course', $header['course'] ?? null, 'Level', $header['level'] ?? null],
            ['Mode', $header['modeOfStudy'] ?? null, 'Year', $header['calendarYear'] ?? null],
            ['Generated', $header['generatedAt'] ?? null],
            [
                'STUDENT_ENROLMENT_ID',
                'STUDENT_NUMBER',
                'STUDENT_NAME',
                'CLASS_NAME',
                'MODULE_ID',
                'MODULE_CODE',
                'MODULE_TITLE',
                'ASSESSMENT_TYPE_ID',
                'ASSESSMENT_NAME',
                'WEIGHT_PERCENT',
                'MARK',
                'REMARK',
            ],
        ];

        foreach ($rows as $row) {
            $output[] = [
                $row['studentEnrolmentId'] ?? null,
                $row['studentNumber'] ?? null,
                $row['studentName'] ?? null,
                $row['className'] ?? null,
                $row['moduleId'] ?? null,
                $row['moduleCode'] ?? null,
                $row['moduleTitle'] ?? null,
                $row['assessmentTypeId'] ?? null,
                $row['assessmentName'] ?? null,
                $row['weightPercent'] ?? null,
                $row['mark'] ?? null,
                $row['remark'] ?? null,
            ];
        }

        return $output;
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
            [__('academic_calendar.course_work_import_instruction_remarks')],
        ];
    }

    public function title(): string
    {
        return 'Instructions';
    }
}
