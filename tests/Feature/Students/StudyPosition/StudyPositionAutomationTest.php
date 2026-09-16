<?php

declare(strict_types=1);

use App\Enums\Students\StudentExamResultComment;
use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentExamResult;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Models\Students\StudentTransfer;
use App\Services\Students\StudyPosition\CurrentStudyPeriodResolver;
use App\Services\Students\StudyPosition\StudyPositionAutoConfirmer;
use App\Services\Students\StudyPosition\StudyPositionAutoDetector;
use App\Services\Students\StudyPosition\StudyPositionDetection;
use App\Services\Students\StudyPosition\StudyPositionService;

function detectStudyPosition(StudentEnrolment $enrolment): StudyPositionDetection
{
    $enrolment = StudentEnrolment::query()->findOrFail($enrolment->id);
    $period = app(CurrentStudyPeriodResolver::class)->forEnrolment($enrolment);

    return app(StudyPositionAutoDetector::class)->detect($enrolment, $period);
}

function autoConfirmStudyPosition(StudentEnrolment $enrolment): string
{
    return app(StudyPositionAutoConfirmer::class)->run(StudentEnrolment::query()->findOrFail($enrolment->id));
}

function seatInStudyPositionClass(
    array $context,
    StudentEnrolment $enrolment,
    ProgrammeSemester $phase,
    bool $live = true,
    string $calendarYear = '2026',
): void {
    // One config per offering, year and phase; each call adds another class under it.
    $config = ClassConfig::query()->firstOrCreate([
        'calendar_year' => $calendarYear,
        'programme_semester_id' => $phase->id,
        'institution_department_id' => $enrolment->institution_department_id,
        'department_course_id' => $enrolment->department_course_id,
        'department_level_id' => $enrolment->department_level_id,
        'mode_of_study_id' => $enrolment->mode_of_study_id,
    ], [
        'semester_id' => $context['slotOne']->id,
        'students_per_class' => 30,
        'course_syllabus_ids' => [],
    ]);

    $class = AcademicCalendarClass::query()->create([
        'tenant_id' => $context['tenantId'],
        'class_config_id' => $config->id,
        'name' => 'CLS-'.uniqid(),
    ]);

    AcademicCalendarStudentEnrolment::query()->create([
        'tenant_id' => $context['tenantId'],
        'academic_calendar_class_id' => $class->id,
        'student_enrolment_id' => $enrolment->id,
        'is_live' => $live,
    ]);
}

function moveStudyPositionEnrolmentTo(StudentEnrolment $enrolment, AcademicCalendar $calendar): StudentEnrolment
{
    StudentEnrolment::withoutEvents(fn () => $enrolment->update(['academic_calendar_id' => $calendar->id]));

    return $enrolment->fresh();
}

function recordStudyPositionExamResult(StudentEnrolment $enrolment, StudentExamResultComment $comment, string $session = '2026-06-15'): void
{
    $student = Student::query()->withoutGlobalScopes()->findOrFail($enrolment->student_id);

    StudentExamResult::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'candidate_number' => 'CAND-'.uniqid(),
        'department_level_id' => $enrolment->department_level_id,
        'department_course_id' => $enrolment->department_course_id,
        'calendar_year' => (int) substr($session, 0, 4),
        'session' => $session,
        'comment' => $comment,
    ]);
}

// ---------------------------------------------------------------- class list rule

it('confirms the phase of a live class seat and moves the records to it', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    seatInStudyPositionClass($context, $enrolment, $y1s2);

    $detection = detectStudyPosition($enrolment);

    expect($detection->isFound())->toBeTrue()
        ->and($detection->source)->toBe(StudyPositionSourceEnum::AUTO_CLASS_LIST)
        ->and((int) $detection->phase->id)->toBe((int) $y1s2->id)
        ->and(autoConfirmStudyPosition($enrolment))->toBe(StudyPositionAutoConfirmer::APPLIED)
        ->and(studyPositionPins($enrolment)['semester-2'])->toBe((int) $y1s2->id);
});

it('ignores stale, non-live and ambiguous class seats', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2, , $y2s2] = $offering['phases'];

    // A Semester 1 class the student was never moved out of does not fit Semester 2.
    $stale = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    seatInStudyPositionClass($context, $stale, $y1s1);

    $notLive = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    seatInStudyPositionClass($context, $notLive, $y1s2, live: false);

    $ambiguous = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    seatInStudyPositionClass($context, $ambiguous, $y1s2);
    seatInStudyPositionClass($context, $ambiguous, $y2s2);

    $otherYear = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    seatInStudyPositionClass($context, $otherYear, $y1s2, calendarYear: '2025');

    expect(detectStudyPosition($stale)->outcome)->toBe(StudyPositionDetection::NONE)
        ->and(detectStudyPosition($notLive)->outcome)->toBe(StudyPositionDetection::NONE)
        ->and(detectStudyPosition($ambiguous)->outcome)->toBe(StudyPositionDetection::NONE)
        ->and(detectStudyPosition($otherYear)->outcome)->toBe(StudyPositionDetection::NONE);
});

it('accepts a mid-year intake sitting Year 1 Sem 1 in Semester 2', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $enrolment = moveStudyPositionEnrolmentTo(
        makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-2', 'phase' => $y1s1]]),
        $context['semesterTwo'],
    );
    seatInStudyPositionClass($context, $enrolment, $y1s1);

    $detection = detectStudyPosition($enrolment);

    expect($detection->source)->toBe(StudyPositionSourceEnum::AUTO_CLASS_LIST)
        ->and((int) $detection->phase->id)->toBe((int) $y1s1->id)
        ->and(autoConfirmStudyPosition($enrolment))->toBe(StudyPositionAutoConfirmer::KEPT);
});

// ---------------------------------------------------------------- exam rule

it('moves a student on after an exam Proceed in the previous period', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    recordStudyPositionExamResult($enrolment, StudentExamResultComment::Proceed);

    $detection = detectStudyPosition($enrolment);

    expect($detection->source)->toBe(StudyPositionSourceEnum::AUTO_EXAM_PROCEED)
        ->and((int) $detection->phase->id)->toBe((int) $y1s2->id)
        ->and(autoConfirmStudyPosition($enrolment))->toBe(StudyPositionAutoConfirmer::APPLIED)
        ->and(studyPositionPins($enrolment)['semester-2'])->toBe((int) $y1s2->id);

    $confirmation = StudentStudyPositionConfirmation::query()->sole();
    expect($confirmation->evidence['session'])->toBe('2026-06-15');
});

it('does not move a student on without a Proceed result', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $referred = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    recordStudyPositionExamResult($referred, StudentExamResultComment::Referred);

    $noResult = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);

    expect(detectStudyPosition($referred)->outcome)->toBe(StudyPositionDetection::NONE)
        ->and(detectStudyPosition($noResult)->outcome)->toBe(StudyPositionDetection::NONE)
        ->and(autoConfirmStudyPosition($noResult))->toBe(StudyPositionAutoConfirmer::NO_EVIDENCE)
        ->and(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

it('does not cross into the next stage on an exam Proceed', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2, $y2s1] = $offering['phases'];

    // A mid-year intake: Year 1 Sem 2 sat in Semester 1, so Year 2 Sem 1 falls in Semester 2.
    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s2]]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => 'semester',
        'opening_date' => '2025-02-01',
        'closing_date' => '2025-06-30',
    ]);
    $lastYear = AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => 'semester',
        'opening_date' => '2025-08-01',
        'closing_date' => '2025-12-10',
    ]);
    addStudyPositionYearEnrolment($enrolment, $lastYear, [['slug' => 'semester-2', 'phase' => $y1s1]]);
    recordStudyPositionExamResult($enrolment, StudentExamResultComment::Proceed);

    expect((int) detectStudyPosition($enrolment)->phase?->id)->toBe((int) $y2s1->id);

    StudentEnrolment::withoutEvents(fn () => $enrolment->update(['programme_stage_id' => $y1s1->programme_stage_id]));

    expect($y1s1->programme_stage_id)->not->toBeNull()
        ->and(detectStudyPosition($enrolment)->outcome)->toBe(StudyPositionDetection::NONE);
});

// ---------------------------------------------------------------- new intake rule

it('confirms a new intake on the first phase', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $enrolment = moveStudyPositionEnrolmentTo(makeEnrolmentWithPhases($context, $offering, []), $context['semesterTwo']);

    $detection = detectStudyPosition($enrolment);

    expect($detection->source)->toBe(StudyPositionSourceEnum::AUTO_NEW_INTAKE)
        ->and((int) $detection->phase->id)->toBe((int) $y1s1->id)
        ->and(autoConfirmStudyPosition($enrolment))->toBe(StudyPositionAutoConfirmer::APPLIED)
        ->and(studyPositionPins($enrolment))->toBe(['semester-2' => (int) $y1s1->id]);
});

it('does not treat transfers or returning students as new intakes', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);

    $transfer = moveStudyPositionEnrolmentTo(makeEnrolmentWithPhases($context, $offering, []), $context['semesterTwo']);
    StudentTransfer::query()->create([
        'tenant_id' => $context['tenantId'],
        'student_id' => $transfer->student_id,
        'student_application_id' => $transfer->student_application_id,
        'college_name' => 'Another College',
    ]);

    $returning = moveStudyPositionEnrolmentTo(makeEnrolmentWithPhases($context, $offering, []), $context['semesterTwo']);
    $lastYear = AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => 'semester',
        'opening_date' => '2025-02-01',
        'closing_date' => '2025-06-30',
    ]);
    addStudyPositionYearEnrolment($returning, $lastYear, []);

    expect(detectStudyPosition($transfer)->outcome)->toBe(StudyPositionDetection::NONE)
        ->and(detectStudyPosition($returning)->outcome)->toBe(StudyPositionDetection::NONE);
});

it('does not treat a late-filed enrolment, a later stage or a mode mismatch as a new intake', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context, years: 2, attachmentSemesters: 2);
    $phases = $offering['phases'];
    [$y1s1, $y1s2, $y2s1] = $phases;

    // Filed against Semester 2 but already holding its Semester 1 row: it was open before this period.
    $lateFiled = moveStudyPositionEnrolmentTo(
        makeEnrolmentWithPhases($context, $offering, [
            ['slug' => 'semester-1', 'phase' => $y1s1],
            ['slug' => 'semester-2', 'phase' => $y1s2],
        ]),
        $context['semesterTwo'],
    );

    // A Year 2 stage application with no Year 1 history is a returning student, not an intake.
    $laterStage = moveStudyPositionEnrolmentTo(makeEnrolmentWithPhases($context, $offering, []), $context['semesterTwo']);
    StudentEnrolment::withoutEvents(fn () => $laterStage->update(['programme_stage_id' => $y2s1->programme_stage_id]));

    // OJET only offers attachment phases, so a taught stage on an OJET enrolment is bad data.
    $ojet = moveStudyPositionEnrolmentTo(makeEnrolmentWithPhases($context, $offering, []), $context['semesterTwo']);
    $ojetMode = App\Models\Institution\ModeOfStudy::query()->create(['name' => 'Ojet']);
    StudentEnrolment::withoutEvents(fn () => $ojet->update([
        'mode_of_study_id' => $ojetMode->id,
        'programme_stage_id' => $y1s1->programme_stage_id,
    ]));

    expect(detectStudyPosition($lateFiled)->outcome)->toBe(StudyPositionDetection::NONE)
        ->and(detectStudyPosition($laterStage)->outcome)->toBe(StudyPositionDetection::NONE)
        ->and(detectStudyPosition($ojet)->outcome)->toBe(StudyPositionDetection::NONE)
        ->and($phases->last()->kind->value)->toBe('industrial_attachment');
});

// ---------------------------------------------------------------- combined behaviour

it('asks the student when the rules disagree', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [, $y1s2] = $offering['phases'];

    // New intake says Year 1 Sem 1, the class list says Year 1 Sem 2.
    $enrolment = moveStudyPositionEnrolmentTo(makeEnrolmentWithPhases($context, $offering, []), $context['semesterTwo']);
    seatInStudyPositionClass($context, $enrolment, $y1s2);

    expect(detectStudyPosition($enrolment)->outcome)->toBe(StudyPositionDetection::CONFLICT)
        ->and(autoConfirmStudyPosition($enrolment))->toBe(StudyPositionAutoConfirmer::CONFLICT)
        ->and(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

it('records nothing when the guarded write refuses the detected phase', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [, $y1s2, $y2s1] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y2s1]]);
    seatInStudyPositionClass($context, $enrolment, $y1s2);

    expect(autoConfirmStudyPosition($enrolment))->toBe(StudyPositionAutoConfirmer::REFUSED)
        ->and(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

it('runs the rules once when a student status is first computed', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    seatInStudyPositionClass($context, $enrolment, $y1s2);
    $student = Student::query()->findOrFail($enrolment->student_id);

    $status = app(StudyPositionService::class)->statusFor($student);

    expect($status['state'])->toBe('confirmed')
        ->and($status['required'])->toBeFalse()
        ->and($status['items'][0]['confirmation']['source'])->toBe('auto_class_list');
});

// ---------------------------------------------------------------- department reconciliation

it('records reconciled and aligned rows as department confirmations for the current year', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $moved = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    $aligned = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ]);
    $forged = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ]);

    $this->postJson(route('department-data-reconciliation.semester-reconciliation.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'rows' => [['rowNumber' => 2, 'studentEnrolmentId' => $moved->id, 'programmeSemesterId' => $y1s2->id]],
        'matchedRows' => [
            ['studentEnrolmentId' => $aligned->id, 'programmeSemesterId' => $y1s2->id],
            // The preview never said this one matched Year 1 Sem 1.
            ['studentEnrolmentId' => $forged->id, 'programmeSemesterId' => $y1s1->id],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.moved', 1)
        ->assertJsonPath('summary.confirmed', 2);

    $confirmations = StudentStudyPositionConfirmation::query()->get()->keyBy('student_enrolment_id');

    expect($confirmations)->toHaveCount(2)
        ->and($confirmations[$moved->id]->source)->toBe(StudyPositionSourceEnum::AUTO_DEPARTMENT_RECONCILIATION)
        ->and($confirmations[$aligned->id]->sync_status)->toBe(StudyPositionSyncStatusEnum::UNCHANGED)
        ->and((int) $confirmations[$aligned->id]->confirmed_by)->toBe((int) $context['user']->id)
        ->and(studyPositionPins($moved))->toBe([
            'semester-1' => (int) $y1s1->id,
            'semester-2' => (int) $y1s2->id,
        ]);
});

it('accepts a run that only confirms aligned rows', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $aligned = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ]);

    $this->postJson(route('department-data-reconciliation.semester-reconciliation.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'matchedRows' => [['studentEnrolmentId' => $aligned->id, 'programmeSemesterId' => $y1s2->id]],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.moved', 0)
        ->assertJsonPath('summary.confirmed', 1);
});

it('does not confirm a back-year reconciliation', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $current = makeEnrolmentWithPhases($context, $offering, []);
    $lastYear = AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => 'semester',
        'opening_date' => '2025-02-01',
        'closing_date' => '2025-06-30',
    ]);
    $past = addStudyPositionYearEnrolment($current, $lastYear, [['slug' => 'semester-1', 'phase' => $y1s1]]);

    $this->postJson(route('department-data-reconciliation.semester-reconciliation.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'rows' => [['rowNumber' => 2, 'studentEnrolmentId' => $past->id, 'programmeSemesterId' => $y1s2->id]],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.moved', 1)
        ->assertJsonPath('summary.confirmed', 0);

    // Back-year files keep the usual slot mapping.
    expect(studyPositionPins($past))->toBe([
        'semester-1' => (int) $y1s1->id,
        'semester-2' => (int) $y1s2->id,
    ])->and(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

// ---------------------------------------------------------------- nightly command

it('reports without writing on a dry run, then confirms once for real', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $enrolment = moveStudyPositionEnrolmentTo(makeEnrolmentWithPhases($context, $offering, []), $context['semesterTwo']);

    $this->artisan('students:auto-confirm-study-position', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(0)
        ->and(studyPositionPins($enrolment))->toBe([]);

    $this->artisan('students:auto-confirm-study-position')->assertSuccessful();
    $this->artisan('students:auto-confirm-study-position')->assertSuccessful();

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(1)
        ->and(studyPositionPins($enrolment))->toBe(['semester-2' => (int) $y1s1->id]);
});

it('settles a review flag once the records hold the answered phase', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ]);

    $confirmation = app(App\Actions\Students\ConfirmStudyPositionAction::class)->execute(
        $enrolment,
        StudyPositionAnswerEnum::PHASE,
        $y1s1,
        StudyPositionSourceEnum::STUDENT,
    );
    expect($confirmation->sync_status)->toBe(StudyPositionSyncStatusEnum::NEEDS_REVIEW);

    // Registry corrects the record by hand: Semester 1 was never sat, Semester 2 is Year 1 Sem 1.
    App\Models\Students\StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $context['slotOne']->id)
        ->forceDelete();
    App\Models\Students\StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $context['slotTwo']->id)
        ->update(['programme_semester_id' => $y1s1->id]);

    $this->artisan('students:auto-confirm-study-position')->assertSuccessful();

    expect($confirmation->fresh()->sync_status)->toBe(StudyPositionSyncStatusEnum::UNCHANGED)
        ->and($confirmation->fresh()->sync_note)->toBeNull();
});
