<?php

namespace App\Console\Commands\Students;

use App\Enums\Institution\GradeEnum;
use App\Enums\Institution\SubjectEnum;
use App\Enums\Shared\AcademicLevelEnum;
use App\Models\Shared\AcademicLevel;
use App\Models\Students\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateBulkDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-bulk-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update bulk data for students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating bulk data for students started');
        $nextYear = Carbon::now()->addYear()->format('y'); // 2-digit next year

        Student::chunk(100, function($students) use ($nextYear) {
            foreach ($students as $student) {
                $studentNumber = $student->student_number;

                // Replace first two characters with next year
                $newStudentNumber = $nextYear . substr($studentNumber, 2);

                // Update the student record
                $student->update(['student_number' => $newStudentNumber]);
            }
        });
        $this->info('Done!');
    }

    private function saveAcademicResults(Student $student): void
    {
        $level = AcademicLevel::where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)->first();
        $subjects = [SubjectEnum::ENGLISH->id() => GradeEnum::C->id(), SubjectEnum::MATHEMATICS->id() => GradeEnum::C->id(), SubjectEnum::INTEGRATED_SCIENCE->id() => GradeEnum::C->id()];
        $otherSubjects = [SubjectEnum::HISTORY->id() => GradeEnum::A->id(), SubjectEnum::SHONA->id() => GradeEnum::B->id()];
        $examYear = 2024;
        $sitting = 'november';
        foreach ($subjects as $subjectId => $gradeId) {
            $student->oLevelResults()->create([
                'academic_level_id' => $level->id,
                'subject_id' => $subjectId,
                'exam_year' => $examYear,
                'exam_sitting' => $sitting,
                'grade_id' => $gradeId,
            ]);
        }

        foreach ($otherSubjects as $subjectId => $gradeId) {
            $student->oLevelResults()->create([
                'academic_level_id' => $level->id,
                'subject_id' => $subjectId,
                'exam_year' => $examYear,
                'exam_sitting' => $sitting,
                'grade_id' => $gradeId,
            ]);
        }
    }
}
