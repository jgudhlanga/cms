<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Enums\Students\StudentExamResultComment;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Examinations\ExaminationResult;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentExamResult;
use App\Models\Students\StudentSemester;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Students\ExamResultLevelResolver;
use App\Services\Students\StudentEnrolmentProgressionService;
use App\Services\Students\StudentSemesterPhaseResolver;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use App\Support\Examinations\HexcoCourseLevelMatcher;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * After a HEXCO AWARD is claimed, close that statement's level and — when a next-level
 * application is already FINAL (or already enrolled) — start the next level at programme
 * Year 1 Sem 1 in the academic half after the sitting.
 */
class StartNextLevelFromHexcoAwardAction
{
    public function __construct(
        protected ExamResultLevelResolver $levelResolver,
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
        protected StudentEnrolmentProgressionService $progression,
        protected StudentSemesterPhaseResolver $phaseResolver,
        protected UpsertYearStudentEnrolmentAction $upsertYearStudentEnrolment,
    ) {}

    /**
     * @return array{
     *     status: string,
     *     student_id: int,
     *     exam_result_id: int|null,
     *     awarded_level: string|null,
     *     next_level: string|null,
     *     student_enrolment_id: int|null,
     *     seated: bool
     * }
     */
    public function execute(Student $student, StudentExamResult $examResult): array
    {
        $base = [
            'student_id' => (int) $student->id,
            'exam_result_id' => (int) $examResult->id,
            'awarded_level' => null,
            'next_level' => null,
            'student_enrolment_id' => null,
            'seated' => false,
        ];

        if ($examResult->comment !== StudentExamResultComment::Award) {
            return [...$base, 'status' => 'skipped_not_award'];
        }

        if ((int) $examResult->student_id !== (int) $student->id) {
            return [...$base, 'status' => 'skipped_student_mismatch'];
        }

        $awardedLevel = $this->resolveAwardedLevel($student, $examResult);

        if (! $awardedLevel instanceof DepartmentLevel) {
            return [...$base, 'status' => 'skipped_unknown_award_level'];
        }

        $base['awarded_level'] = (string) ($awardedLevel->level?->name ?? '');

        $this->closeAwardedLevelIfPresent($student, $awardedLevel);

        $nextLevel = $this->nextDepartmentLevel($awardedLevel);

        if (! $nextLevel instanceof DepartmentLevel) {
            return [...$base, 'status' => 'closed_terminal'];
        }

        $base['next_level'] = (string) ($nextLevel->level?->name ?? '');

        $application = $this->nextLevelApplication($student, $awardedLevel, $nextLevel, $examResult);

        if (! $application instanceof StudentApplication) {
            return [...$base, 'status' => 'skipped_no_application'];
        }

        if (! $this->applicationMayStart($application)) {
            return [...$base, 'status' => 'skipped_application_not_final'];
        }

        $targetCalendar = $this->targetCalendarAfterSitting($student, $examResult, $nextLevel);

        if (! $targetCalendar instanceof AcademicCalendar) {
            Log::warning('exam_results.award_next_level_no_calendar', [
                'student_id' => $student->id,
                'exam_result_id' => $examResult->id,
                'session' => $examResult->session,
            ]);

            return [...$base, 'status' => 'skipped_no_target_calendar'];
        }

        return DB::transaction(function () use ($base, $application, $targetCalendar, $nextLevel): array {
            $asOf = Carbon::parse($targetCalendar->opening_date)->startOfDay();
            $enrolment = $this->upsertYearStudentEnrolment->execute($application, $asOf);
            $enrolment = $this->alignEnrolmentToTargetHalf($enrolment, $targetCalendar, $nextLevel);
            $seated = $this->seatIfClassExists($enrolment);

            return [
                ...$base,
                'status' => 'started',
                'student_enrolment_id' => (int) $enrolment->id,
                'seated' => $seated,
            ];
        });
    }

    private function resolveAwardedLevel(Student $student, StudentExamResult $examResult): ?DepartmentLevel
    {
        $courseLevel = ExaminationResult::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $student->tenant_id)
            ->where('candidate_number', $examResult->candidate_number)
            ->where('session', $examResult->session)
            ->whereNotNull('course_level')
            ->value('course_level');

        // Prefer the statement's COURSE LEVEL, then the level already filed on the claimed
        // summary. Only fall back to session/enrolment heuristics when neither is known —
        // otherwise a prior-level award (NC while the student is on ND) is mis-read as the
        // current enrolment and written onto Year 1 Sem 1.
        if (is_string($courseLevel) && trim($courseLevel) !== '') {
            $matched = HexcoCourseLevelMatcher::match($courseLevel);

            if ($matched !== null) {
                $departmentId = $examResult->institution_department_id
                    ?? StudentEnrolment::query()
                        ->where('student_id', $student->id)
                        ->value('institution_department_id');

                if ($departmentId !== null) {
                    $fromCourseLevel = DepartmentLevel::query()
                        ->where('institution_department_id', $departmentId)
                        ->whereHas('level', fn ($query) => $query->where('name', $matched->name()))
                        ->with('level')
                        ->first();

                    if ($fromCourseLevel instanceof DepartmentLevel) {
                        return $fromCourseLevel;
                    }
                }
            }
        }

        if ($examResult->department_level_id !== null) {
            $filed = DepartmentLevel::query()
                ->with('level')
                ->find($examResult->department_level_id);

            if ($filed instanceof DepartmentLevel) {
                return $filed;
            }
        }

        $currentEnrolment = StudentEnrolment::query()
            ->where('student_id', $student->id)
            ->with(['departmentLevel.level', 'academicCalendar'])
            ->get()
            ->sortByDesc(fn (StudentEnrolment $enrolment): string => (string) ($enrolment->academicCalendar?->opening_date ?? ''))
            ->first();

        return $this->levelResolver->resolve(
            $student,
            is_string($courseLevel) ? $courseLevel : null,
            (string) $examResult->session,
            $currentEnrolment,
        )?->loadMissing('level');
    }

    private function closeAwardedLevelIfPresent(Student $student, DepartmentLevel $awardedLevel): void
    {
        $enrolment = StudentEnrolment::query()
            ->where('student_id', $student->id)
            ->where('department_level_id', $awardedLevel->id)
            ->with(['studentSemesters.semester', 'studentSemesters.programmeSemester'])
            ->orderByDesc('id')
            ->first();

        if (! $enrolment instanceof StudentEnrolment) {
            return;
        }

        $awardStatusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_AWARD);

        if ($awardStatusId === null) {
            return;
        }

        $completionPhase = $enrolment->studentSemesters->first(
            fn (StudentSemester $phase): bool => $this->programmeSemesterResolver->isCompletionProgrammeSemester($phase),
        );

        if (! $completionPhase instanceof StudentSemester) {
            return;
        }

        if ((int) $completionPhase->student_enrolment_status_id !== $awardStatusId) {
            $this->progression->updateStudentSemesterStatus($completionPhase, $awardStatusId);
        }
    }

    private function nextDepartmentLevel(DepartmentLevel $awardedLevel): ?DepartmentLevel
    {
        $awardedLevel->loadMissing('level');
        $position = (int) ($awardedLevel->level?->position ?? 0);

        if ($position === 0) {
            return null;
        }

        return DepartmentLevel::query()
            ->where('institution_department_id', $awardedLevel->institution_department_id)
            ->whereNull('deleted_at')
            ->whereHas('level', fn ($query) => $query->where('position', '>', $position)->whereNull('deleted_at'))
            ->with('level')
            ->get()
            ->sortBy(fn (DepartmentLevel $departmentLevel): int => (int) ($departmentLevel->level?->position ?? 0))
            ->first();
    }

    private function nextLevelApplication(
        Student $student,
        DepartmentLevel $awardedLevel,
        DepartmentLevel $nextLevel,
        StudentExamResult $examResult,
    ): ?StudentApplication {
        $courseId = $examResult->department_course_id
            ?? StudentEnrolment::query()
                ->where('student_id', $student->id)
                ->where('department_level_id', $awardedLevel->id)
                ->value('department_course_id')
            ?? StudentEnrolment::query()
                ->where('student_id', $student->id)
                ->where('department_level_id', $nextLevel->id)
                ->value('department_course_id');

        $query = StudentApplication::query()
            ->where('student_id', $student->id)
            ->where('department_level_id', $nextLevel->id)
            ->where('institution_department_id', $nextLevel->institution_department_id)
            ->with(['classList', 'workflowStep'])
            ->where(function ($builder): void {
                $builder->whereDoesntHave('workflowStep')
                    ->orWhereHas(
                        'workflowStep',
                        fn ($step) => $step->where('slug', '!=', WorkflowStepEnum::REJECTED->slug()),
                    );
            });

        if ($courseId !== null) {
            $query->where('department_course_id', $courseId);
        }

        return $query->orderByDesc('id')->first();
    }

    private function applicationMayStart(StudentApplication $application): bool
    {
        $application->loadMissing('classList');

        if ($application->classList?->type === ClassListTypeEnum::FINAL) {
            return true;
        }

        return StudentEnrolment::query()
            ->where('student_application_id', $application->id)
            ->whereNull('deleted_at')
            ->exists();
    }

    private function targetCalendarAfterSitting(
        Student $student,
        StudentExamResult $examResult,
        DepartmentLevel $nextLevel,
    ): ?AcademicCalendar {
        $nextLevel->loadMissing('level');
        $calendarType = $nextLevel->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
                ?? AcademicCalendarTypeEnum::SEMESTER;
        }

        $sessionDate = $this->parseSession($examResult->session);

        if (! $sessionDate instanceof CarbonInterface) {
            return null;
        }

        $sittingPeriod = AcademicCalendar::resolveCurrentPeriodForDate(
            (string) $sessionDate->year,
            $calendarType,
            $sessionDate,
        );

        if (! $sittingPeriod instanceof AcademicCalendar) {
            $sittingPeriod = AcademicCalendar::query()
                ->where('type', $calendarType)
                ->whereDate('opening_date', '<=', $sessionDate->toDateString())
                ->whereDate('closing_date', '>=', $sessionDate->toDateString())
                ->orderBy('opening_date')
                ->first();
        }

        return AcademicCalendar::resolveNextPeriodAfter($sittingPeriod);
    }

    private function alignEnrolmentToTargetHalf(
        StudentEnrolment $enrolment,
        AcademicCalendar $targetCalendar,
        DepartmentLevel $nextLevel,
    ): StudentEnrolment {
        $targetSlug = AcademicCalendarPeriodResolver::semesterSlugForCalendar($targetCalendar);
        $targetSemesterId = $this->phaseResolver->phaseOptions(
            $nextLevel->level?->calendar_type instanceof AcademicCalendarTypeEnum
                ? $nextLevel->level->calendar_type
                : AcademicCalendarTypeEnum::SEMESTER,
        )->firstWhere('slug', $targetSlug)?->id
            ?? $enrolment->semester_id;

        $activeStatusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_ACTIVE);

        StudentEnrolment::withoutEvents(function () use ($enrolment, $targetCalendar, $targetSemesterId, $activeStatusId): void {
            $enrolment->update(array_filter([
                'academic_calendar_id' => $targetCalendar->id,
                'semester_id' => $targetSemesterId,
                'student_enrolment_status_id' => $activeStatusId,
            ], static fn ($value): bool => $value !== null));
        });

        $enrolment = $enrolment->fresh(['studentSemesters.semester', 'studentSemesters.studentEnrolmentStatus'])
            ?? $enrolment;

        $this->collapsePhasesBeforeStart($enrolment, $targetSlug);

        $enrolment = $enrolment->fresh(['studentSemesters.semester', 'studentSemesters.studentEnrolmentStatus'])
            ?? $enrolment;

        $firstTaught = $this->firstTaughtProgrammeSemester($enrolment);
        $startPhase = $enrolment->studentSemesters
            ->sortBy(fn (StudentSemester $phase): int => $this->phaseResolver
                ->phaseOrdinal((string) ($phase->semester?->slug ?? '')))
            ->first();

        if ($startPhase instanceof StudentSemester) {
            $attributes = [];

            if ($firstTaught instanceof ProgrammeSemester) {
                $attributes['programme_semester_id'] = $firstTaught->id;
            }

            if ($activeStatusId !== null) {
                $attributes['student_enrolment_status_id'] = $activeStatusId;
            }

            if ($targetSemesterId !== null) {
                $attributes['semester_id'] = $targetSemesterId;
            }

            if ($attributes !== []) {
                $startPhase->update($attributes);
            }
        }

        $enrolment = $enrolment->fresh(['studentSemesters.semester', 'academicCalendar', 'semester'])
            ?? $enrolment;

        $this->progression->pinSyllabusFromMatchingClassConfig($enrolment);

        return $enrolment->fresh(['studentSemesters.semester', 'studentSemesters.studentEnrolmentStatus', 'academicCalendar'])
            ?? $enrolment;
    }

    private function collapsePhasesBeforeStart(StudentEnrolment $enrolment, string $startSlug): void
    {
        $startOrdinal = $this->phaseResolver->phaseOrdinal($startSlug);

        if ($startOrdinal <= 1) {
            return;
        }

        $enrolment->loadMissing(['studentSemesters.semester', 'studentSemesters.studentEnrolmentStatus']);

        foreach ($enrolment->studentSemesters as $phase) {
            $ordinal = $this->phaseResolver->phaseOrdinal((string) ($phase->semester?->slug ?? ''));

            if ($ordinal >= $startOrdinal) {
                continue;
            }

            if ($this->phaseIsInUse($phase)) {
                continue;
            }

            $phase->delete();
        }
    }

    private function phaseIsInUse(StudentSemester $phase): bool
    {
        if (($phase->course_syllabus_ids ?? []) !== []) {
            return true;
        }

        if (StudentEnrolmentProgressionService::isBlockingStatus($this->progression->statusSlugForSemester($phase))) {
            return true;
        }

        return AcademicCalendarStudentEnrolment::query()
            ->where('student_semesters_id', $phase->id)
            ->exists();
    }

    private function firstTaughtProgrammeSemester(StudentEnrolment $enrolment): ?ProgrammeSemester
    {
        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null) {
            return null;
        }

        $taught = $this->programmeSemesterResolver->taughtProgrammeSemesters($dlc);

        return $taught[0] ?? null;
    }

    private function seatIfClassExists(StudentEnrolment $enrolment): bool
    {
        $studentSemester = $this->progression->currentStudentSemester($enrolment);
        $classConfig = $this->progression->matchingClassConfig($enrolment, $studentSemester);

        if (! $classConfig instanceof ClassConfig || ! $studentSemester instanceof StudentSemester) {
            return false;
        }

        $targetClass = AcademicCalendarClass::query()
            ->where('class_config_id', $classConfig->id)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        if (! $targetClass instanceof AcademicCalendarClass) {
            return false;
        }

        $existingLive = AcademicCalendarStudentEnrolment::query()
            ->where('student_enrolment_id', $enrolment->id)
            ->where('academic_calendar_class_id', $targetClass->id)
            ->where('is_live', true)
            ->whereNull('deleted_at')
            ->first();

        if ($existingLive instanceof AcademicCalendarStudentEnrolment) {
            if ((int) $existingLive->student_semesters_id !== (int) $studentSemester->id) {
                $existingLive->update(['student_semesters_id' => $studentSemester->id]);
            }

            return true;
        }

        AcademicCalendarStudentEnrolment::query()
            ->where('student_enrolment_id', $enrolment->id)
            ->where('is_live', true)
            ->whereNull('deleted_at')
            ->update([
                'is_live' => false,
                'concluded_at' => now(),
            ]);

        AcademicCalendarStudentEnrolment::query()->create([
            'student_enrolment_id' => $enrolment->id,
            'student_semesters_id' => $studentSemester->id,
            'academic_calendar_class_id' => $targetClass->id,
            'is_live' => true,
        ]);

        return true;
    }

    private function parseSession(?string $session): ?CarbonInterface
    {
        if ($session === null || trim($session) === '') {
            return null;
        }

        try {
            return Carbon::parse($session)->startOfDay();
        } catch (\Exception) {
            return null;
        }
    }
}
