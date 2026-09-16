<?php

declare(strict_types=1);

use App\Actions\Students\ConfirmStudyPositionAction;
use App\Actions\Students\SetStudentEnrolmentCurrentPhaseAction;
use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Services\Students\StudyPosition\StudyPositionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

function confirmStudyPosition(
    StudentEnrolment $enrolment,
    StudyPositionAnswerEnum $answer,
    mixed $phase,
    StudyPositionSourceEnum $source = StudyPositionSourceEnum::STUDENT,
    ?string $reason = null,
): ?StudentStudyPositionConfirmation {
    return app(ConfirmStudyPositionAction::class)->execute(
        StudentEnrolment::query()->findOrFail($enrolment->id),
        $answer,
        $phase,
        $source,
        auth()->user(),
        $reason,
    );
}

// ---------------------------------------------------------------- slot-aware write

it('pins the given calendar slot instead of the phase usual slot', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, []);

    app(SetStudentEnrolmentCurrentPhaseAction::class)->execute($enrolment, $y1s1, $context['slotTwo']);

    expect(studyPositionPins($enrolment))->toBe(['semester-2' => (int) $y1s1->id]);
});

it('allows a backward correction on the row being re-pinned', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    // Mis-pinned: the Semester 1 row claims Year 1 Sem 2.
    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s2]]);

    app(SetStudentEnrolmentCurrentPhaseAction::class)->execute($enrolment, $y1s1);

    expect(studyPositionPins($enrolment))->toBe(['semester-1' => (int) $y1s1->id]);
});

it('rejects a slot from another calendar type', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    $enrolment = makeEnrolmentWithPhases($context, $offering, []);
    $termSlot = Semester::query()->firstOrCreate(['slug' => 'term-1'], ['name' => 'Term 1', 'description' => null]);

    expect(fn () => app(SetStudentEnrolmentCurrentPhaseAction::class)
        ->execute($enrolment, $offering['phases'][0], $termSlot))
        ->toThrow(InvalidArgumentException::class);
});

// ---------------------------------------------------------------- confirmation outcomes

it('records an unchanged confirmation when the records already agree', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ]);
    $before = studyPositionPins($enrolment);

    $confirmation = confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s2);

    expect($confirmation->sync_status)->toBe(StudyPositionSyncStatusEnum::UNCHANGED)
        ->and((int) $confirmation->programme_semester_id)->toBe((int) $y1s2->id)
        ->and((int) $confirmation->academic_calendar_id)->toBe((int) $context['semesterTwo']->id)
        ->and((int) $confirmation->tenant_id)->toBe((int) $context['tenantId'])
        ->and(studyPositionPins($enrolment))->toBe($before);
});

it('applies a different phase to the current slot and audits the move', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);

    $confirmation = confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s2);

    expect($confirmation->sync_status)->toBe(StudyPositionSyncStatusEnum::APPLIED)
        ->and((int) $confirmation->previous_programme_semester_id)->toBe((int) $y1s1->id)
        ->and($confirmation->student_semester_id)->not->toBeNull()
        ->and(studyPositionPins($enrolment))->toBe([
            'semester-1' => (int) $y1s1->id,
            'semester-2' => (int) $y1s2->id,
        ]);

    $activity = Activity::query()->where('event', 'study-position-confirmed')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->getExtraProperty('old_programme_semester_id'))->toBe((int) $y1s1->id)
        ->and($activity->getExtraProperty('new_programme_semester_id'))->toBe((int) $y1s2->id)
        ->and($activity->getExtraProperty('source'))->toBe('student')
        ->and((int) $activity->causer_id)->toBe((int) $context['user']->id);
});

it('corrects a mid-year intake pinned to the wrong phase in the current slot', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-2', 'phase' => $y1s2]]);

    $confirmation = confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s1);

    expect($confirmation->sync_status)->toBe(StudyPositionSyncStatusEnum::APPLIED)
        ->and(studyPositionPins($enrolment))->toBe(['semester-2' => (int) $y1s1->id]);
});

it('keeps the answer for review when the phase is already recorded earlier this year', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ]);
    $before = studyPositionPins($enrolment);

    $confirmation = confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s1);

    expect($confirmation->sync_status)->toBe(StudyPositionSyncStatusEnum::NEEDS_REVIEW)
        ->and($confirmation->sync_note)->not->toBeEmpty()
        ->and((int) $confirmation->programme_semester_id)->toBe((int) $y1s1->id)
        ->and(studyPositionPins($enrolment))->toBe($before);
});

it('keeps the answer for review when a later phase is already recorded', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [, $y1s2, $y2s1] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y2s1]]);
    $before = studyPositionPins($enrolment);

    $confirmation = confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s2);

    expect($confirmation->sync_status)->toBe(StudyPositionSyncStatusEnum::NEEDS_REVIEW)
        ->and(studyPositionPins($enrolment))->toBe($before);
});

it('flags a repeat of a phase held in an earlier year for review, but lets an admin record it', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);

    $lastYear = AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => 'semester',
        'opening_date' => '2025-02-01',
        'closing_date' => '2025-06-30',
    ]);
    addStudyPositionYearEnrolment($enrolment, $lastYear, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ]);

    $studentAnswer = confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s2);

    expect($studentAnswer->sync_status)->toBe(StudyPositionSyncStatusEnum::NEEDS_REVIEW)
        ->and($studentAnswer->sync_note)->toContain('2025')
        ->and(studyPositionPins($enrolment))->toBe(['semester-1' => (int) $y1s1->id]);

    $adminAnswer = confirmStudyPosition(
        $enrolment,
        StudyPositionAnswerEnum::PHASE,
        $y1s2,
        StudyPositionSourceEnum::ADMIN,
        'Repeating Year 1 Sem 2 per HOD register',
    );

    expect($adminAnswer->sync_status)->toBe(StudyPositionSyncStatusEnum::APPLIED)
        ->and($adminAnswer->source)->toBe(StudyPositionSourceEnum::ADMIN)
        ->and($adminAnswer->reason)->toBe('Repeating Year 1 Sem 2 per HOD register')
        ->and(studyPositionPins($enrolment)['semester-2'])->toBe((int) $y1s2->id);
});

it('tells an admin why a refused write failed and saves nothing', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ]);

    expect(fn () => confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s1, StudyPositionSourceEnum::ADMIN, 'Checked'))
        ->toThrow(ValidationException::class);

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

it('records a follow-up answer without touching the records', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);

    $confirmation = confirmStudyPosition($enrolment, StudyPositionAnswerEnum::NOT_SURE, $y1s1);

    expect($confirmation->sync_status)->toBe(StudyPositionSyncStatusEnum::NOT_APPLICABLE)
        ->and($confirmation->programme_semester_id)->toBeNull()
        ->and((int) $confirmation->previous_programme_semester_id)->toBe((int) $y1s1->id)
        ->and(studyPositionPins($enrolment))->toBe(['semester-1' => (int) $y1s1->id]);
});

it('lets a student settle their own follow-up answer but not an admin confirmation', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);

    confirmStudyPosition($enrolment, StudyPositionAnswerEnum::WRONG_PROGRAMME, null);
    $settled = confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s2);

    expect($settled->answer)->toBe(StudyPositionAnswerEnum::PHASE)
        ->and(StudentStudyPositionConfirmation::query()->count())->toBe(1);

    expect(fn () => confirmStudyPosition($enrolment, StudyPositionAnswerEnum::NOT_SURE, null))
        ->toThrow(ValidationException::class);

    confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s2, StudyPositionSourceEnum::ADMIN, 'Confirmed');

    expect(fn () => confirmStudyPosition($enrolment, StudyPositionAnswerEnum::NOT_SURE, null))
        ->toThrow(ValidationException::class);
});

it('never lets an automatic rule overwrite a person', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);

    confirmStudyPosition($enrolment, StudyPositionAnswerEnum::NOT_SURE, null);
    $result = confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s2, StudyPositionSourceEnum::AUTO_CLASS_LIST);

    expect($result->source)->toBe(StudyPositionSourceEnum::STUDENT)
        ->and($result->answer)->toBe(StudyPositionAnswerEnum::NOT_SURE)
        ->and(studyPositionPins($enrolment))->toBe(['semester-1' => (int) $y1s1->id]);
});

it('flags a student answer the department reconciliation disagrees with', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);

    confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s2);
    $result = confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s1, StudyPositionSourceEnum::AUTO_DEPARTMENT_RECONCILIATION);

    expect($result->source)->toBe(StudyPositionSourceEnum::STUDENT)
        ->and($result->sync_status)->toBe(StudyPositionSyncStatusEnum::NEEDS_REVIEW);
});

it('keeps a deferred status when applying a phase', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];
    $deferredId = (int) $context['statuses']['deferred']->id;

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]], statusId: $deferredId);

    confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s2);

    $row = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('programme_semester_id', $y1s2->id)
        ->first();

    expect((int) $row->student_enrolment_status_id)->toBe($deferredId)
        ->and((int) $enrolment->fresh()->student_enrolment_status_id)->toBe($deferredId);
});

it('refuses out-of-scope enrolments loudly for people and quietly for rules', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases(
        $context,
        $offering,
        [['slug' => 'semester-1', 'phase' => $y1s1]],
        statusId: (int) $context['statuses']['award']->id,
    );

    expect(fn () => confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s1))
        ->toThrow(ValidationException::class)
        ->and(confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $y1s1, StudyPositionSourceEnum::AUTO_NEW_INTAKE))
        ->toBeNull();
});

it('rejects a phase the student is not offered', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    $other = makeMultiYearOffering($context);

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $offering['phases'][0]]]);

    expect(fn () => confirmStudyPosition($enrolment, StudyPositionAnswerEnum::PHASE, $other['phases'][1]))
        ->toThrow(ValidationException::class);
});

it('clears the cached prompt summary after a confirmation', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    $key = 'study-position:prompt:'.$enrolment->student_id;
    Cache::put($key, ['fingerprint' => 'x', 'summary' => null]);

    confirmStudyPosition($enrolment, StudyPositionAnswerEnum::NOT_SURE, null);

    expect(Cache::has($key))->toBeFalse();

    StudyPositionService::forget((int) $enrolment->student_id);
});
