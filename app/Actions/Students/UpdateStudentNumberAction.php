<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\Finance\PastelLinkedStudent;
use App\Models\Students\Student;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStudentNumberAction
{
    public function execute(Student $student, string $studentNumber, string $reason, ?User $actor = null): Student
    {
        $previousNumber = (string) $student->student_number;

        if ($previousNumber === $studentNumber) {
            throw ValidationException::withMessages([
                'student_number' => __('students.change_student_number_unchanged'),
            ]);
        }

        $this->assertStudentNumberIsAvailable($student, $studentNumber);

        DB::transaction(function () use ($student, $studentNumber): void {
            $student->update(['student_number' => $studentNumber]);

            PastelLinkedStudent::query()
                ->where('student_id', $student->id)
                ->update(['student_number' => $studentNumber]);
        });

        $this->audit($student, $actor, $previousNumber, $studentNumber, $reason);

        return $student->refresh();
    }

    private function assertStudentNumberIsAvailable(Student $student, string $studentNumber): void
    {
        // The unique index on students.student_number is not soft-delete aware,
        // so archived records must be checked too.
        $conflict = Student::withTrashed()
            ->with('user')
            ->where('student_number', $studentNumber)
            ->whereKeyNot($student->getKey())
            ->first();

        if ($conflict instanceof Student) {
            throw ValidationException::withMessages([
                'student_number' => __(
                    $conflict->trashed()
                        ? 'students.change_student_number_taken_archived'
                        : 'students.change_student_number_taken',
                    [
                        'number' => $studentNumber,
                        'name' => $conflict->user?->full_name ?? __('students.change_student_number_conflict_unnamed'),
                    ],
                ),
            ]);
        }

        $financeConflict = PastelLinkedStudent::query()
            ->where('student_number', $studentNumber)
            ->where('student_id', '!=', $student->id)
            ->exists();

        if ($financeConflict) {
            throw ValidationException::withMessages([
                'student_number' => __('students.change_student_number_taken_finance', [
                    'number' => $studentNumber,
                ]),
            ]);
        }
    }

    private function audit(
        Student $student,
        ?User $actor,
        string $previousNumber,
        string $studentNumber,
        string $reason,
    ): void {
        $logger = activity('Student')
            ->performedOn($student)
            ->event('student-number-changed')
            ->withProperties([
                'old_student_number' => $previousNumber !== '' ? $previousNumber : null,
                'new_student_number' => $studentNumber,
                'reason' => $reason,
            ]);

        if ($actor !== null) {
            $logger->causedBy($actor);
        }

        $logger->log(__('students.change_student_number_activity_description', ['number' => $studentNumber]));
    }
}
