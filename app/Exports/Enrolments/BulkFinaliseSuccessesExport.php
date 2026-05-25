<?php

namespace App\Exports\Enrolments;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class BulkFinaliseSuccessesExport implements FromArray, WithTitle
{
    /**
     * @param  array<int, array{student_program_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}>  $successes
     */
    public function __construct(private readonly array $successes) {}

    /**
     * @return array<int, array<int, int|string|null>>
     */
    public function array(): array
    {
        /** @var array<string, array<string, array<string, array<int, array{student_program_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}>>>> $grouped */
        $grouped = [];

        foreach ($this->successes as $success) {
            $department = $this->safeGroupLabel($success['department']);
            $course = $this->safeGroupLabel($success['course']);
            $level = $this->safeGroupLabel($success['level']);

            $grouped[$department][$course][$level][] = $success;
        }

        ksort($grouped);

        $rows = [[
            'name',
            'department',
            'level',
            'course',
            'idNumber',
            'studentNumber',
            'reason',
            'classListId',
            'studentProgramId',
        ]];

        foreach ($grouped as $department => $courses) {
            ksort($courses);

            foreach ($courses as $course => $levels) {
                ksort($levels);

                foreach ($levels as $level => $entries) {
                    $rows[] = [
                        "Group: {$department} / {$course} / {$level}",
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                    ];

                    foreach ($entries as $entry) {
                        $rows[] = [
                            $entry['user_full_name'],
                            $department,
                            $level,
                            $course,
                            $entry['student_id_number'],
                            $entry['student_number'],
                            $entry['reason'],
                            $entry['class_list_id'],
                            $entry['student_program_id'],
                        ];
                    }
                }
            }
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Would Finalise';
    }

    private function safeGroupLabel(?string $value): string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : 'Unknown';
    }
}
