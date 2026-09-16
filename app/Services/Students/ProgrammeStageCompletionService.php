<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Institution\ProgrammeStage;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentProgrammeStage;
use App\Models\Students\StudentSemester;
use Illuminate\Support\Carbon;

class ProgrammeStageCompletionService
{
    /**
     * @var list<string>
     */
    public const COMPLETING_STATUSES = [
        StudentEnrolmentProgressionService::STATUS_PROCEED,
        StudentEnrolmentProgressionService::STATUS_AWARD,
    ];

    /**
     * A stage is complete when every programme semester in it has a completing
     * student_semesters row, regardless of academic calendar year.
     */
    public function isStageComplete(Student $student, ProgrammeStage $stage): bool
    {
        $stage->loadMissing('programmeSemesters');
        $periods = $stage->programmeSemesters;

        if ($periods->isEmpty()) {
            return false;
        }

        foreach ($periods as $programmeSemester) {
            if (! $this->hasCompletingInclusion($student, (int) $programmeSemester->id)) {
                return false;
            }
        }

        return true;
    }

    public function recordIfComplete(
        Student $student,
        ProgrammeStage $stage,
        ?StudentApplication $application = null,
    ): ?StudentProgrammeStage {
        $existing = StudentProgrammeStage::query()
            ->where('student_id', $student->id)
            ->where('programme_stage_id', $stage->id)
            ->first();

        if (! $this->isStageComplete($student, $stage)) {
            return $existing;
        }

        $attributes = [
            'student_id' => $student->id,
            'student_application_id' => $application?->id ?? $existing?->student_application_id,
            'department_level_course_id' => $stage->department_level_course_id,
            'programme_stage_id' => $stage->id,
            'completed_at' => $existing?->completed_at ?? Carbon::now(),
        ];

        if ($existing instanceof StudentProgrammeStage) {
            if ($existing->completed_at === null) {
                $existing->update($attributes);
            }

            return $existing->fresh() ?? $existing;
        }

        return StudentProgrammeStage::query()->create($attributes);
    }

    public function isRecordedComplete(Student $student, ProgrammeStage $stage): bool
    {
        return StudentProgrammeStage::query()
            ->where('student_id', $student->id)
            ->where('programme_stage_id', $stage->id)
            ->whereNotNull('completed_at')
            ->exists();
    }

    private function hasCompletingInclusion(Student $student, int $programmeSemesterId): bool
    {
        return StudentSemester::query()
            ->where('programme_semester_id', $programmeSemesterId)
            ->whereNull('deleted_at')
            ->whereHas('enrolment', function ($query) use ($student): void {
                $query
                    ->where('student_id', $student->id)
                    ->whereNull('deleted_at');
            })
            ->whereHas('studentEnrolmentStatus', function ($query): void {
                $query->whereIn('slug', self::COMPLETING_STATUSES);
            })
            ->exists();
    }
}
