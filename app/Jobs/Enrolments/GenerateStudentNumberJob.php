<?php

namespace App\Jobs\Enrolments;

use App\Helpers\EnrolmentHelper;
use App\Models\Students\StudentProgram;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class GenerateStudentNumberJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ?array $studentPrograms = null,
    )
    {
    }

    public function handle(): void
    {
        StudentProgram::query()
            ->with([
                'student',
                'institutionDepartment',
                'departmentLevel.level',
                'classList',
            ])
            ->whereHas('student', fn($q) => $q->where(fn($s) => $s->where('student_number_generated', false)
                ->orWhereNull('student_number_generated')))
            ->whereHas('classList', fn($q) => $q->where('type', 'verified'))
            ->when($this->studentPrograms, fn($q) => $q->whereIn('id', $this->studentPrograms))
            ->chunkById(100, fn($programs) => $programs->each(fn($program) => $this->processProgram($program))
            );
    }

    private function processProgram(StudentProgram $program): void
    {
        $student = $program->student;
        if ($student->student_number_generated) {
            return; // skip race-condition duplicates
        }
        $studentNumber = EnrolmentHelper::resolveStudentNumber($program);
        DB::transaction(function () use ($student, $program, $studentNumber) {
            $student->fresh()->update([
                'student_number' => $studentNumber,
                'student_number_generated' => true,
            ]);
            // send email with offer letter
            $user = $student->user;
            SendOfferLetterJob::dispatch($user->full_name, $user->email, $program->id)->withoutDelay();
            if (EnrolmentHelper::isEntryLevel($program)) {
                EnrolmentHelper::rejectOtherApplications($student, $program);
            }
        });
    }
}
