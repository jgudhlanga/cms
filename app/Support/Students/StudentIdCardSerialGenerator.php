<?php

declare(strict_types=1);

namespace App\Support\Students;

use App\Models\Students\Student;
use App\Models\Students\StudentIdCardRequest;

class StudentIdCardSerialGenerator
{
    public function generate(Student $student, StudentIdCardRequest $request): string
    {
        $studentNumber = preg_replace('/[^A-Za-z0-9]/', '', (string) $student->student_number) ?: 'UNKNOWN';
        $prefix = (string) config('id_cards.serial_prefix', 'HPC');

        return strtoupper(sprintf('%s-%s-%d', $prefix, $studentNumber, $request->id));
    }
}
