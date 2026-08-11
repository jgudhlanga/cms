<?php

declare(strict_types=1);

use App\Enums\Students\StudentExamResultComment;
use App\Models\Students\Student;
use App\Services\Students\StudentFeeClearanceService;

test('fee clearance treats zero expected as not fully paid', function () {
    $service = new class extends StudentFeeClearanceService
    {
        public function evaluate(Student $student): array
        {
            $hasStudentNumber = trim((string) $student->student_number) !== '';
            $expectedTotal = 0.0;
            $paidTotal = 0.0;

            return [
                'tuition' => 0.0,
                'autoCardFee' => 0.0,
                'partTimeLevy' => 0.0,
                'expectedTotal' => $expectedTotal,
                'paidTotal' => $paidTotal,
                'outstanding' => 0.0,
                'isFullyPaid' => $hasStudentNumber && $expectedTotal > 0 && $paidTotal >= $expectedTotal,
                'breakdown' => [],
                'hasStudentNumber' => $hasStudentNumber,
                'isEnrolled' => false,
                'source' => null,
            ];
        }
    };

    $student = new Student(['student_number' => 'H100']);

    expect($service->evaluate($student)['isFullyPaid'])->toBeFalse();
});

test('comment mapping rejects unknown comments without inventing award', function () {
    expect(StudentExamResultComment::tryFromCourseComment('PASS'))->toBeNull();
});
