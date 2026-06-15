<?php

namespace App\Exports\AcademicCalendars;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class CourseWorkMarksheetExport implements WithMultipleSheets
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
            new CourseWorkMarksheetMainSheetExport($this->data),
        ];

        $issues = $this->data['issues'] ?? [];
        if ($issues !== []) {
            $sheets[] = new CourseWorkMarksheetIssuesSheetExport($issues);
        }

        return $sheets;
    }
}

class CourseWorkMarksheetMainSheetExport implements FromArray, WithTitle
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
        /** @var list<array{id: int, name: string, weightPercent: int|null}> $assessmentTypes */
        $assessmentTypes = $this->data['assessmentTypes'] ?? [];
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->data['rows'] ?? [];

        $output = [
            ['HEEDCO Coursework / Examinations Mark Schedule'],
            ['Centre', $header['centre'] ?? null, 'Centre No', $header['centreNumber'] ?? null],
            ['Level', $header['level'] ?? null, 'Discipline', $header['discipline'] ?? null],
            ['Course', $header['course'] ?? null, 'Subject Code', $header['subjectCode'] ?? null],
            ['Subject', $header['subject'] ?? null, 'Session', $header['session'] ?? null],
            ['Class', $header['className'] ?? null, 'Generated', $header['generatedAt'] ?? null],
            [],
        ];

        $columnHeaders = [
            'Candidate Number',
            'Candidate Name',
        ];

        foreach ($assessmentTypes as $type) {
            $weight = $type['weightPercent'] ?? '';
            $columnHeaders[] = sprintf('%s (%s%%)', $type['name'], $weight);
        }

        $columnHeaders = array_merge($columnHeaders, [
            'Course Work Total (60%)',
            'Exam Total (40%)',
            'Final Mark (100%)',
            'Remark',
        ]);

        $output[] = $columnHeaders;

        foreach ($rows as $row) {
            /** @var list<array{assessmentTypeId: int, rawMark: int|null}> $components */
            $components = $row['components'] ?? [];
            $marksByType = collect($components)->keyBy('assessmentTypeId');

            $line = [
                $row['candidateNumber'] ?? null,
                $row['name'] ?? null,
            ];

            foreach ($assessmentTypes as $type) {
                $component = $marksByType->get((int) $type['id']);
                $line[] = $component['rawMark'] ?? null;
            }

            $line[] = $row['courseWorkTotal60'] ?? null;
            $line[] = null;
            $line[] = null;
            $line[] = $row['remark'] ?? null;

            $output[] = $line;
        }

        return $output;
    }

    public function title(): string
    {
        /** @var array<string, mixed> $header */
        $header = $this->data['header'] ?? [];
        $code = trim((string) ($header['subjectCode'] ?? 'Marksheet'));

        return strlen($code) > 31 ? substr($code, 0, 31) : $code;
    }
}

class CourseWorkMarksheetIssuesSheetExport implements FromArray, WithTitle
{
    /**
     * @param  list<array{studentEnrolmentId: int, studentName: string, issue: string}>  $issues
     */
    public function __construct(private readonly array $issues) {}

    /**
     * @return array<int, array<int|string|null>>
     */
    public function array(): array
    {
        $rows = [['Student Enrolment ID', 'Student Name', 'Issue']];

        foreach ($this->issues as $issue) {
            $rows[] = [
                $issue['studentEnrolmentId'],
                $issue['studentName'],
                $issue['issue'],
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Issues';
    }
}
