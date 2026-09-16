<?php

declare(strict_types=1);

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudyPosition\CurrentStudyPeriodResolver;
use App\Services\Students\StudyPosition\StudyPositionScope;
use Illuminate\Support\Carbon;

function inStudyPositionScope(StudentEnrolment $enrolment): bool
{
    return app(StudyPositionScope::class)
        ->constrain(StudentEnrolment::query()->whereKey($enrolment->id))
        ->exists();
}

it('resolves the most recently opened period with its slot and label', function (): void {
    $context = makeStudyPositionContext('2026-09-10');

    $period = app(CurrentStudyPeriodResolver::class)->forType(AcademicCalendarTypeEnum::SEMESTER);

    expect($period)->not->toBeNull()
        ->and($period->periodId())->toBe((int) $context['semesterTwo']->id)
        ->and($period->slot->slug)->toBe('semester-2')
        ->and($period->label)->toBe('2026 · Semester 2')
        ->and($period->yearPeriodIds)->toEqualCanonicalizing([
            (int) $context['semesterOne']->id,
            (int) $context['semesterTwo']->id,
        ]);
});

it('keeps the previous period current through the holiday gap', function (): void {
    $context = makeStudyPositionContext('2026-07-15');

    $period = app(CurrentStudyPeriodResolver::class)->forType(AcademicCalendarTypeEnum::SEMESTER);

    expect($period?->periodId())->toBe((int) $context['semesterOne']->id);
});

it('finds the previous period across a calendar year boundary', function (): void {
    $context = makeStudyPositionContext('2026-03-01');

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

    $resolver = app(CurrentStudyPeriodResolver::class);
    $current = $resolver->forType(AcademicCalendarTypeEnum::SEMESTER);

    expect($current?->periodId())->toBe((int) $context['semesterOne']->id)
        ->and($resolver->previous($current)?->periodId())->toBe((int) $lastYear->id)
        ->and($resolver->previous($current)?->slot->slug)->toBe('semester-2');
});

it('refreshes the current period when a calendar is saved', function (): void {
    Carbon::setTestNow('2026-09-10');

    foreach (['semester-1' => 'Semester 1', 'semester-2' => 'Semester 2'] as $slug => $name) {
        Semester::query()->firstOrCreate(['slug' => $slug], ['name' => $name, 'description' => null]);
    }

    $first = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);

    $resolver = app(CurrentStudyPeriodResolver::class);
    expect($resolver->forType(AcademicCalendarTypeEnum::SEMESTER)?->periodId())->toBe((int) $first->id);

    $second = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-08-01',
        'closing_date' => '2026-12-10',
    ]);

    expect($resolver->forType(AcademicCalendarTypeEnum::SEMESTER)?->periodId())->toBe((int) $second->id);
});

it('scopes current-year enrolments and leaves out past years', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    $phases = $offering['phases'];

    $current = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $phases[0]]]);

    $lastYear = AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => 'semester',
        'opening_date' => '2025-02-01',
        'closing_date' => '2025-06-30',
    ]);
    $past = addStudyPositionYearEnrolment($current, $lastYear, [['slug' => 'semester-1', 'phase' => $phases[0]]]);

    expect(inStudyPositionScope($current))->toBeTrue()
        ->and(inStudyPositionScope($past))->toBeFalse();
});

it('leaves out awarded and disqualified enrolments but keeps deferred ones', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    $phases = $offering['phases'];
    $statuses = $context['statuses'];

    $awarded = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $phases[0]]], statusId: (int) $statuses['award']->id);
    $disqualified = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $phases[0]]], statusId: (int) $statuses['disqualified']->id);
    $deferred = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $phases[0]]], statusId: (int) $statuses['deferred']->id);

    // Award on any phase row closes the level even if the enrolment snapshot says otherwise.
    $awardOnRow = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $phases[0]]]);
    StudentSemester::query()->where('student_enrolment_id', $awardOnRow->id)
        ->update(['student_enrolment_status_id' => $statuses['award']->id]);

    expect(inStudyPositionScope($awarded))->toBeFalse()
        ->and(inStudyPositionScope($disqualified))->toBeFalse()
        ->and(inStudyPositionScope($awardOnRow))->toBeFalse()
        ->and(inStudyPositionScope($deferred))->toBeTrue();
});

it('leaves out offerings without programme phases', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $offering['phases'][0]]]);

    expect(inStudyPositionScope($enrolment))->toBeTrue();

    ProgrammeSemester::query()->where('department_level_course_id', $offering['offering']->id)->delete();

    expect(inStudyPositionScope($enrolment))->toBeFalse();
});

it('counts only the newest enrolment of an application within the year', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    $phases = $offering['phases'];

    $older = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $phases[0]]]);
    $newer = addStudyPositionYearEnrolment($older, $context['semesterTwo'], [['slug' => 'semester-2', 'phase' => $phases[1]]]);

    expect(inStudyPositionScope($older))->toBeFalse()
        ->and(inStudyPositionScope($newer))->toBeTrue();
});

it('scopes nothing when no period of the type has opened', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $offering['phases'][0]]]);

    Carbon::setTestNow('2020-01-01');
    app(CurrentStudyPeriodResolver::class)->forget();

    expect(inStudyPositionScope($enrolment))->toBeFalse();
});
