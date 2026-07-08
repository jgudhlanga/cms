<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\StudentApplication;
use App\Services\HMS\HostelApplicationSemesterService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

beforeEach(function (): void {
    foreach (['Term 1', 'Term 2', 'Semester 1', 'Semester 2'] as $name) {
        AcademicYearOption::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }
});

it('resolves current semester dates for a student enrolment', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    $studentApplication = createStudentReadyForHostelApplication('SEM-SERVICE-001');
    $calendar = AcademicCalendar::query()
        ->where('calendar_year', '2025/2026')
        ->where('type', AcademicCalendarTypeEnum::SEMESTER)
        ->orderBy('opening_date')
        ->firstOrFail();

    $service = app(HostelApplicationSemesterService::class);
    $result = $service->datesForApplication($studentApplication->student);

    expect($result['success'])->toBeTrue()
        ->and($result['checkIn'])->toBe(Carbon::parse($calendar->opening_date)->toDateString())
        ->and($result['checkOut'])->toBe(Carbon::parse($calendar->closing_date)->toDateString())
        ->and($result['label'])->toBe('Semester 1');
});

it('keeps current semester dates during inter-semester gap', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15', config('app.timezone')));

    $studentApplication = createVerifiedStudentApplication('SEM-SERVICE-GAP');
    $studentApplication->intakePeriod()->update(['calendar_year' => '2025/2026']);

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    attachHostelApplicationEnrolment(
        $studentApplication,
        AcademicCalendar::query()
            ->where('calendar_year', '2025/2026')
            ->whereDate('opening_date', '2026-01-15')
            ->firstOrFail(),
    );

    $service = app(HostelApplicationSemesterService::class);
    $result = $service->datesForApplication($studentApplication->student->fresh(['latestEnrolment']));

    expect($result['success'])->toBeTrue()
        ->and($result['checkIn'])->toBe('2026-01-15')
        ->and($result['checkOut'])->toBe('2026-06-30')
        ->and($result['label'])->toBe('Semester 1');
});

it('returns no running semester blocker when no matching calendar year exists', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    $studentApplication = createVerifiedStudentApplication('SEM-SERVICE-002');
    $studentApplication->intakePeriod()->update(['calendar_year' => '2099/2100']);

    $calendar2026 = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-12-15',
    ]);

    attachHostelApplicationEnrolment($studentApplication, $calendar2026);

    $service = app(HostelApplicationSemesterService::class);
    $result = $service->datesForApplication($studentApplication->student->fresh(['latestEnrolment']));

    expect($result['success'])->toBeFalse()
        ->and($result['blocker'])->toBe(HostelApplicationSemesterService::BLOCKER_NO_RUNNING_SEMESTER);
});

it('resolves term calendars for term-based courses', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    $studentApplication = createStudentReadyForHostelApplication('SEM-SERVICE-TERM', withRunningSemester: false);
    $studentApplication->departmentLevel->level->update(['calendar_type' => 'term']);

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::TERM,
        'opening_date' => '2026-02-03',
        'closing_date' => '2026-04-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::TERM,
        'opening_date' => '2026-05-01',
        'closing_date' => '2026-08-30',
    ]);

    $service = app(HostelApplicationSemesterService::class);
    $result = $service->datesForApplication($studentApplication->student->fresh());

    expect($result['success'])->toBeTrue()
        ->and($result['checkIn'])->toBe('2026-02-03')
        ->and($result['checkOut'])->toBe('2026-04-30')
        ->and($result['label'])->toBe('Term 1');
});

it('returns upcoming semester dates before academic year starts', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-11-01', config('app.timezone')));

    $studentApplication = createVerifiedStudentApplication('SEM-SERVICE-PRE');
    $studentApplication->intakePeriod()->update(['calendar_year' => '2025/2026']);

    $upcomingSemester = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    attachHostelApplicationEnrolment($studentApplication, $upcomingSemester);

    $service = app(HostelApplicationSemesterService::class);
    $result = $service->datesForApplication($studentApplication->student->fresh(['latestEnrolment']));

    expect($result['success'])->toBeTrue()
        ->and($result['checkIn'])->toBe('2026-01-15')
        ->and($result['checkOut'])->toBe('2026-04-30')
        ->and($result['label'])->toBe('Semester 1');
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});
