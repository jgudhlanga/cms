<?php

declare(strict_types=1);

namespace App\Services\Students\StudyPosition;

use App\Enums\Students\StudentExamResultComment;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentModuleExemption;
use App\Models\Students\StudentSemester;
use App\Models\Students\StudentTransfer;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Students\ExamResultEnrolmentStatusResolver;
use Illuminate\Support\Collection;

/**
 * Infers the phase an enrolment is studying this period from evidence already on record, so the
 * obvious cases never reach the student. Every rule must fit the current calendar slot; a student
 * who fell off-cycle fails the fit and is simply asked. False negatives are cheap, false positives
 * are not.
 *
 * Department semester reconciliation is recorded when the import runs, not detected here.
 */
class StudyPositionAutoDetector
{
    public function __construct(
        private readonly ProgrammeSemesterResolver $programmeSemesterResolver,
        private readonly CurrentStudyPeriodResolver $periods,
        private readonly ExamResultEnrolmentStatusResolver $examStatus,
    ) {}

    public function detect(StudentEnrolment $enrolment, CurrentStudyPeriod $period): StudyPositionDetection
    {
        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        if (! $dlc instanceof DepartmentLevelCourse || $dlc->programmeSemesters->isEmpty()) {
            return StudyPositionDetection::none();
        }

        $offered = $this->offeredPhases($enrolment, $dlc);
        $startOrdinal = $this->programmeSemesterResolver->intakeStartOrdinal($enrolment);

        // Highest precedence first: a department-built class list beats inference from results or dates.
        $findings = array_values(array_filter([
            $this->fromLiveClass($enrolment, $period, $dlc, $offered, $startOrdinal),
            $this->fromExamProceed($enrolment, $period, $dlc, $offered, $startOrdinal),
            $this->fromNewIntake($enrolment, $period, $dlc, $offered, $startOrdinal),
        ]));

        if ($findings === []) {
            return StudyPositionDetection::none();
        }

        $phaseIds = array_unique(array_map(static fn (StudyPositionDetection $d): int => (int) $d->phase?->id, $findings));

        if (count($phaseIds) > 1) {
            return StudyPositionDetection::conflict([
                'rules' => array_map(static fn (StudyPositionDetection $d): array => [
                    'source' => $d->source?->value,
                    'programme_semester_id' => $d->phase?->id,
                ], $findings),
            ]);
        }

        return $findings[0];
    }

    /**
     * @param  Collection<int, ProgrammeSemester>  $offered
     */
    private function fromLiveClass(
        StudentEnrolment $enrolment,
        CurrentStudyPeriod $period,
        DepartmentLevelCourse $dlc,
        Collection $offered,
        int $startOrdinal,
    ): ?StudyPositionDetection {
        $seats = AcademicCalendarStudentEnrolment::query()
            ->withoutGlobalScopes()
            ->join('academic_calendar_classes as sp_class', 'sp_class.id', '=', 'academic_calendar_student_enrolments.academic_calendar_class_id')
            ->join('class_configs as sp_config', 'sp_config.id', '=', 'sp_class.class_config_id')
            ->where('academic_calendar_student_enrolments.student_enrolment_id', $enrolment->id)
            ->where('academic_calendar_student_enrolments.is_live', true)
            ->whereNull('academic_calendar_student_enrolments.deleted_at')
            ->whereNull('sp_class.deleted_at')
            ->whereNull('sp_config.deleted_at')
            ->where('sp_config.calendar_year', $period->calendarYear)
            ->whereNotNull('sp_config.programme_semester_id')
            ->get([
                'academic_calendar_student_enrolments.academic_calendar_class_id as class_id',
                'sp_config.programme_semester_id as phase_id',
            ]);

        // A seat left over from a phase nobody moved the student out of does not fit this slot and drops out.
        $phases = $seats
            ->map(fn ($seat): ?ProgrammeSemester => $offered->first(
                fn (ProgrammeSemester $phase): bool => (int) $phase->id === (int) $seat->getAttribute('phase_id'),
            ))
            ->filter(fn (?ProgrammeSemester $phase): bool => $phase instanceof ProgrammeSemester
                && $this->programmeSemesterResolver->phaseFitsCalendarSlot($dlc, $phase, $period->slot, $startOrdinal))
            ->unique('id')
            ->values();

        if ($phases->count() !== 1) {
            return null;
        }

        return StudyPositionDetection::found($phases->first(), StudyPositionSourceEnum::AUTO_CLASS_LIST, [
            'academic_calendar_class_ids' => $seats->pluck('class_id')->map(fn ($id): int => (int) $id)->values()->all(),
        ]);
    }

    /**
     * @param  Collection<int, ProgrammeSemester>  $offered
     */
    private function fromExamProceed(
        StudentEnrolment $enrolment,
        CurrentStudyPeriod $period,
        DepartmentLevelCourse $dlc,
        Collection $offered,
        int $startOrdinal,
    ): ?StudyPositionDetection {
        $previous = $this->periods->previous($period);

        if (! $previous instanceof CurrentStudyPeriod) {
            return null;
        }

        $metadata = $this->examStatus->resolveMetadataForLevel(
            (int) $enrolment->student_id,
            (int) $enrolment->department_level_id,
            $previous->calendarYear,
            $period->type,
        )->get((string) $previous->slot->slug);

        if (! is_array($metadata) || $metadata['comment'] !== StudentExamResultComment::Proceed) {
            return null;
        }

        $previousRow = StudentSemester::query()
            ->join('student_enrolments as sp_prev', 'sp_prev.id', '=', 'student_semesters.student_enrolment_id')
            ->where('sp_prev.student_id', $enrolment->student_id)
            ->where('sp_prev.department_level_id', $enrolment->department_level_id)
            ->where('sp_prev.department_course_id', $enrolment->department_course_id)
            ->whereIn('sp_prev.academic_calendar_id', $previous->yearPeriodIds)
            ->whereNull('sp_prev.deleted_at')
            ->where('student_semesters.semester_id', $previous->slot->id)
            ->whereNotNull('student_semesters.programme_semester_id')
            ->orderByDesc('sp_prev.id')
            ->with('programmeSemester')
            ->first(['student_semesters.*']);

        $passedPhase = $previousRow?->programmeSemester;

        if (! $passedPhase instanceof ProgrammeSemester) {
            return null;
        }

        $next = $offered
            ->filter(fn (ProgrammeSemester $phase): bool => (int) $phase->position > (int) $passedPhase->position)
            ->sortBy('position')
            ->first();

        if (! $next instanceof ProgrammeSemester) {
            return null;
        }

        // Crossing into another year's stage needs that stage's application first.
        if ($enrolment->programme_stage_id !== null
            && $next->programme_stage_id !== null
            && (int) $next->programme_stage_id !== (int) $enrolment->programme_stage_id) {
            return null;
        }

        if (! $this->programmeSemesterResolver->phaseFitsCalendarSlot($dlc, $next, $period->slot, $startOrdinal)) {
            return null;
        }

        return StudyPositionDetection::found($next, StudyPositionSourceEnum::AUTO_EXAM_PROCEED, [
            'previous_academic_calendar_id' => $previous->periodId(),
            'previous_student_semester_id' => (int) $previousRow->id,
            'session' => $metadata['session'],
            'candidate_number' => $metadata['candidateNumber'],
        ]);
    }

    /**
     * @param  Collection<int, ProgrammeSemester>  $offered
     */
    private function fromNewIntake(
        StudentEnrolment $enrolment,
        CurrentStudyPeriod $period,
        DepartmentLevelCourse $dlc,
        Collection $offered,
        int $startOrdinal,
    ): ?StudyPositionDetection {
        if ((int) $enrolment->academic_calendar_id !== $period->periodId()) {
            return null;
        }

        $hasHistory = StudentEnrolment::query()
            ->where('student_id', $enrolment->student_id)
            ->where('department_level_id', $enrolment->department_level_id)
            ->where('department_course_id', $enrolment->department_course_id)
            ->whereKeyNot($enrolment->id)
            ->exists();

        if ($hasHistory) {
            return null;
        }

        $applicationId = $enrolment->student_application_id;

        // Transfers and exempted students may start part-way through; they have to say where.
        if ($applicationId !== null && (
            StudentTransfer::query()->withoutGlobalScopes()->where('student_application_id', $applicationId)->exists()
            || StudentModuleExemption::query()->where('student_application_id', $applicationId)->exists()
        )) {
            return null;
        }

        // A new intake starts the programme: the first phase the mode offers. A later-stage application
        // is a returning student whose history is simply missing, and a stage the mode does not offer
        // is bad data; either way the student has to say where they are.
        $first = $offered->first();

        if (! $first instanceof ProgrammeSemester) {
            return null;
        }

        if ($enrolment->programme_stage_id !== null
            && (int) $first->programme_stage_id !== (int) $enrolment->programme_stage_id) {
            return null;
        }

        // Rows for earlier slots this year mean the enrolment was not opened this period, whatever
        // calendar it was later filed against.
        $enrolment->loadMissing('studentSemesters.semester');
        $currentOrdinal = $this->slotOrdinal((string) $period->slot->slug);

        if ($enrolment->studentSemesters->contains(
            fn (StudentSemester $row): bool => $this->slotOrdinal((string) $row->semester?->slug) < $currentOrdinal,
        )) {
            return null;
        }

        // Their first period defines their offset, so the first phase always fits unless the calendar type differs.
        if (! $this->programmeSemesterResolver->phaseFitsCalendarSlot($dlc, $first, $period->slot, $startOrdinal)) {
            return null;
        }

        return StudyPositionDetection::found($first, StudyPositionSourceEnum::AUTO_NEW_INTAKE, [
            'academic_calendar_id' => (int) $enrolment->academic_calendar_id,
            'student_application_id' => $applicationId !== null ? (int) $applicationId : null,
        ]);
    }

    private function slotOrdinal(string $slug): int
    {
        $parts = explode('-', $slug);

        return (int) end($parts);
    }

    /**
     * @return Collection<int, ProgrammeSemester>
     */
    private function offeredPhases(StudentEnrolment $enrolment, DepartmentLevelCourse $dlc): Collection
    {
        $enrolment->loadMissing('modeOfStudy');
        $isOjetMode = (bool) $enrolment->modeOfStudy?->isOjet();

        return $dlc->programmeSemesters
            ->filter(fn (ProgrammeSemester $phase): bool => $phase->isOfferedInMode($isOjetMode))
            ->sortBy('position')
            ->values();
    }
}
