<?php

namespace App\Console\Commands\Students;

use App\Enums\Shared\AcademicLevelEnum;
use App\Models\Institution\Subject;
use App\Models\Students\Student;
use App\Models\Students\StudentAcademicResult;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FixOLevelResultsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:fix-o-level-results
                            {student_id? : Fix for a single student}
                            {--force : Permanently delete duplicates instead of soft-deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Fix O-Level results by removing duplicates and restoring latest records, optionally for a specific student.';

    public function handle(): void
    {
        $this->info('🧹 Starting O-Level result cleanup...');

        $studentId = $this->argument('student_id');
        $forceDelete = $this->option('force');

        // Preload subjects to reduce DB calls
        $subjectNames = ['any science subject', 'physics', 'chemistry', 'biology', 'combined science', 'physical science', 'computer science'];

        $subjectMap = Subject::all()
            ->filter(fn($s) => in_array(strtolower(trim($s->name)), $subjectNames))
            ->keyBy(fn($s) => strtolower(trim($s->name)));

        $stats = ['processed' => 0, 'skipped' => 0, 'fixed' => 0];

        if ($studentId) {
            $student = Student::find($studentId);

            if (!$student) {
                $this->error("❌ Student with ID {$studentId} not found.");
                return;
            }

            $this->processStudent($student, $subjectMap, $forceDelete, $stats);
        } else {
            Student::chunk(100, function ($students) use ($subjectMap, $forceDelete, &$stats) {
                foreach ($students as $student) {
                    $this->processStudent($student, $subjectMap, $forceDelete, $stats);
                }
            });
        }

        $this->info("✅ Cleanup completed. Processed: {$stats['processed']} | Fixed: {$stats['fixed']} | Skipped: {$stats['skipped']}");
    }

    protected function processStudent(Student $student, Collection $subjectMap, bool $forceDelete, array &$stats): void
    {
        $stats['processed']++;

        DB::transaction(function () use ($student, $subjectMap, $forceDelete, &$stats) {
            $this->line("======= Start science subject fix for Student ID {$student->id} =======");
            $scienceCreated = $this->scienceSubjectFix($student, $subjectMap);
            $this->line("======= End science subject fix =======");

            $this->line("======= Start duplicates cleanup =======");
            $cleaned = $this->cleanStudentOLevelResults($student, $forceDelete);
            $this->line("======= End duplicates cleanup =======");

            if ($scienceCreated || $cleaned) {
                $stats['fixed']++;
            } else {
                $stats['skipped']++;
            }
        });
    }

    /**
     * Clean duplicate O-Level results for a single student.
     */
    protected function cleanStudentOLevelResults(Student $student, bool $forceDelete): bool
    {
        $results = StudentAcademicResult::withTrashed()
            ->where('student_id', $student->id)
            ->where('academic_level_id', AcademicLevelEnum::SECONDARY_SCHOOL->id())
            ->orderByDesc('id')
            ->get();

        if ($results->isEmpty()) {
            $this->line("👤 Student {$student->id}: No O-Level results found.");
            return false;
        }

        // Group by subject, keep latest per subject
        $latestPerSubject = $results
            ->groupBy('subject_id')
            ->map(fn(Collection $group) => $group->sortByDesc('id')->first())
            ->values();

        // Restore latest if soft-deleted
        foreach ($latestPerSubject as $result) {
            if ($result->trashed()) {
                $result->restore();
                $this->info("🔄 Restored subject {$result->subject_id} (Result ID {$result->id}) for Student {$student->id}");
            }
        }

        // Soft-delete or permanently delete duplicates
        foreach ($latestPerSubject as $result) {
            $duplicates = $results
                ->where('subject_id', $result->subject_id)
                ->where('id', '!=', $result->id)
                ->whereNull('deleted_at');

            foreach ($duplicates as $dup) {
                if ($forceDelete) {
                    $dup->forceDelete();
                    $this->line("🗑️ Permanently deleted duplicate ID {$dup->id} for subject {$dup->subject_id}");
                } else {
                    $dup->delete();
                    $this->line("💤 Soft-deleted duplicate ID {$dup->id} for subject {$dup->subject_id}");
                }
            }
        }

        $this->info("✅ Student {$student->id}: Cleanup complete — kept {$latestPerSubject->count()} unique subjects.");
        return true;
    }

    protected function scienceSubjectFix(Student $student, Collection $subjectMap): bool
    {
        $scienceSubject = $subjectMap->get('any science subject');
        if (!$scienceSubject) {
            $this->warn("⚠️ 'Science' subject not found in database!");
            return false;
        }
        $results = StudentAcademicResult::withTrashed()
            ->where('student_id', $student->id)
            ->where('academic_level_id', AcademicLevelEnum::SECONDARY_SCHOOL->id())
            ->orderByDesc('id')
            ->get();
        if ($results->isEmpty()) {
            $this->line("👤 Student {$student->id}: No O-Level results found.");
            return false;
        }
        // If “Science” result exists, restore if soft-deleted
        $existingScience = $results->firstWhere('subject_id', $scienceSubject->id);
        if ($existingScience) {
            // check if there are any science subjects with exam year less than the existing science subject
            $subjectNames = ['any science subject', 'physics', 'chemistry', 'biology', 'combined science', 'physical science', 'computer science'];
            $otherScienceIds = collect($subjectNames)
                ->filter(fn($n) => $n !== 'any science subject')
                ->map(fn($n) => $subjectMap[$n]->id ?? null)
                ->filter()
                ->values()
                ->toArray();
            $scienceResults = $results->whereIn('subject_id', $otherScienceIds)->where('exam_year', '<', $existingScience->exam_year);
            $bestScienceResult = $scienceResults->sortBy([['grade_id', 'asc'], ['exam_year', 'asc']])->first();
            if ($bestScienceResult) {
                $existingScience->update([
                    'exam_year' => $bestScienceResult->exam_year,
                    'exam_sitting' => $bestScienceResult->exam_sitting,
                    'grade_id' => $bestScienceResult->grade_id
                ]);
                $this->line("👤 Student {$student->id}: already has Science subject. and updated to earlier exam year {$bestScienceResult->exam_year}.");
            } else {
                $this->line("👤 Student {$student->id}: already has Science subject.");
            }
            return false;
        }
        // Other science-type subjects
        $subjectNames = ['any science subject', 'physics', 'chemistry', 'biology', 'combined science', 'physical science', 'computer science'];
        $otherScienceIds = collect($subjectNames)
            ->filter(fn($n) => $n !== 'any science subject')
            ->map(fn($n) => $subjectMap[$n]->id ?? null)
            ->filter()
            ->values()
            ->toArray();
        $scienceResults = $results->whereIn('subject_id', $otherScienceIds);
        if ($scienceResults->isEmpty()) {
            $this->line("👤 Student {$student->id}: No matching science subject results found.");
            return false;
        }
        $bestScienceResult = $scienceResults->sortBy([['grade_id', 'asc'], ['exam_year', 'asc']])->first();
        $bestScienceResult->subject_id = $scienceSubject->id;
        $bestScienceResult->save();
        $this->line("🧪 Updated Science result (ID {$bestScienceResult->id}) from best science-type subject.");
        return true;
    }
}
