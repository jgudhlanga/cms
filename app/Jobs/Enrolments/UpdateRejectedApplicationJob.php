<?php

namespace App\Jobs\Enrolments;

use App\Helpers\EnrolmentHelper;
use App\Models\Students\StudentApplication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class UpdateRejectedApplicationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(  protected ?array $studentApplications = null)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        StudentApplication::query()
            ->with([
                'student',
                'institutionDepartment',
                'departmentLevel.level',
                'classList',
            ])
            ->whereHas('student')
            ->whereDoesntHave('classList')
            ->when($this->studentApplications, fn($q) => $q->whereIn('id', $this->studentApplications))
            ->chunkById(100, fn($programs) => $programs->each(fn($program) => $this->processProgram($program))
            );
    }

    private function processProgram(StudentApplication $program): void
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
            //SendOfferLetterJob::dispatch($user->full_name, $user->email, $program->id)->withoutDelay();
        });
    }
}
