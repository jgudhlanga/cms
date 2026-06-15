<?php

namespace App\Services\Finance;

use App\Models\Students\Student;

class StudentBankStatementMatchPatterns
{
    /**
     * @return array{
     *     exactLikePatterns: array<int, string>,
     *     boundaryPrefixes: array<int, string>
     * }
     */
    public static function forStudent(Student $student): array
    {
        $studentNumber = trim((string) $student->student_number);

        if ($studentNumber === '') {
            return [
                'exactLikePatterns' => [],
                'boundaryPrefixes' => [],
            ];
        }

        return [
            'exactLikePatterns' => [self::toLikePattern($studentNumber)],
            'boundaryPrefixes' => [],
        ];
    }

    private static function toLikePattern(string $value): string
    {
        return '%'.addcslashes($value, '\%_').'%';
    }

}
