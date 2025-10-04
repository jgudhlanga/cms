<?php

namespace App\Console\Commands\Students;

use App\Enums\Shared\AcademicLevelEnum;
use App\Models\Students\Student;
use App\Models\Students\StudentAcademicResult;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DeleteDuplicateOLevelResultsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-duplicate-o-level-results-command {student_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete duplicate O-Level results for each student, keeping only the latest result per subject.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('🧹 Starting duplicate O-Level result cleanup...');

        $studentId = $this->argument('student_id');

        // Run for one student (if student_id provided), or chunk through all students
        if ($studentId) {
            $student = Student::find($studentId);

            if (!$student) {
                $this->error("❌ Student with ID {$studentId} not found.");
                return;
            }

            $this->cleanStudentOLevelResults($student);
        } else {
            Student::chunk(100, function ($students) {
                foreach ($students as $student) {
                    $this->cleanStudentOLevelResults($student);
                }
            });
        }

        $this->info('✅ Duplicate cleanup completed.');
    }

    /**
     * Clean duplicate O-Level results for a single student.
     */
    protected function cleanStudentOLevelResults(Student $student): void
    {
        // 1️⃣ Fetch all O-Level results including soft-deleted
        $results = StudentAcademicResult::withTrashed()
            ->where('student_id', $student->id)
            ->where('academic_level_id', AcademicLevelEnum::SECONDARY_SCHOOL->id()) // 2 = O-Level / Secondary
            ->orderByDesc('id')
            ->get();

        if ($results->isEmpty()) {
            $this->line("👤 Student {$student->id}: No O-Level results found.");
            return;
        }

        // 2️⃣ Group by subject and get the latest record for each subject
        $latestPerSubject = $results
            ->groupBy('subject_id')
            ->map(fn(Collection $group) => $group->sortByDesc('id')->first())
            ->values();

        // 3️⃣ Restore latest records if soft-deleted
        foreach ($latestPerSubject as $result) {
            if ($result->trashed()) {
                $result->restore();
                $this->info("👤 Student {$student->id}: Restored subject {$result->subject_id} (ID {$result->id})");
            }
        }

        // 4️⃣ Soft-delete older duplicates for each subject
        foreach ($latestPerSubject as $result) {
            $duplicates = $results
                ->where('subject_id', $result->subject_id)
                ->where('id', '!=', $result->id)
                ->whereNull('deleted_at');

            foreach ($duplicates as $dup) {
                $dup->delete();
                $this->line("👤 Student {$student->id}: Deleted duplicate ID {$dup->id} for subject {$dup->subject_id}");
            }
        }

        $this->info("👤 Student {$student->id}: Cleanup complete — kept " . $latestPerSubject->count() . " unique subjects.");
    }
}
