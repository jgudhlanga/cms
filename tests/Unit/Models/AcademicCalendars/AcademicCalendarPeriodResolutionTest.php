<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow(null);
});

test('resolveCurrentPeriodForDate returns semester one during its active window', function (): void {
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

    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    $current = AcademicCalendar::resolveCurrentPeriodForDate('2025/2026', AcademicCalendarTypeEnum::SEMESTER);

    expect($current)->not->toBeNull()
        ->and((string) $current->opening_date)->toBe('2026-01-15');
});

test('resolveCurrentPeriodForDate keeps semester one current during inter-semester gap', function (): void {
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

    Carbon::setTestNow(Carbon::parse('2026-05-15', config('app.timezone')));

    $current = AcademicCalendar::resolveCurrentPeriodForDate('2025/2026', AcademicCalendarTypeEnum::SEMESTER);

    expect($current)->not->toBeNull()
        ->and((string) $current->opening_date)->toBe('2026-01-15');
});

test('resolveCurrentPeriodForDate returns semester two when inside second window', function (): void {
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);
    $semesterTwo = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-09-15', config('app.timezone')));

    $current = AcademicCalendar::resolveCurrentPeriodForDate('2026', AcademicCalendarTypeEnum::SEMESTER);

    expect($current?->id)->toBe($semesterTwo->id);
});

test('resolveCurrentPeriodForDate returns null before the first period opens', function (): void {
    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    Carbon::setTestNow(Carbon::parse('2025-11-01', config('app.timezone')));

    expect(AcademicCalendar::resolveCurrentPeriodForDate('2025/2026', AcademicCalendarTypeEnum::SEMESTER))->toBeNull();
});

test('resolveUpcomingPeriodForDate returns first future period when none is current', function (): void {
    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    Carbon::setTestNow(Carbon::parse('2025-11-01', config('app.timezone')));

    $upcoming = AcademicCalendar::resolveUpcomingPeriodForDate('2025/2026', AcademicCalendarTypeEnum::SEMESTER);

    expect($upcoming)->not->toBeNull()
        ->and((string) $upcoming->opening_date)->toBe('2026-01-15');
});

test('resolveNextPeriodAfter returns next semester in the same calendar year', function (): void {
    $semesterOne = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);
    $semesterTwo = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    expect(AcademicCalendar::resolveNextPeriodAfter($semesterOne)?->id)->toBe($semesterTwo->id);
});

test('resolveNextPeriodAfter returns first period in the next calendar year when none remain', function (): void {
    $semesterTwo = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);
    $nextYearSemester = AcademicCalendar::query()->create([
        'calendar_year' => '2026/2027',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2027-01-15',
        'closing_date' => '2027-04-30',
    ]);

    expect(AcademicCalendar::resolveNextPeriodAfter($semesterTwo)?->id)->toBe($nextYearSemester->id);
});

test('resolveCurrentPeriodForDate scopes by calendar year label exactly', function (): void {
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-12-15',
    ]);
    $matching = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-12-15',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    $current = AcademicCalendar::resolveCurrentPeriodForDate('2025/2026', AcademicCalendarTypeEnum::SEMESTER);

    expect($current?->id)->toBe($matching->id);
});

test('currentSemesterSlugForYear stays inside the selected calendar year', function (): void {
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2025-07-01',
        'closing_date' => '2025-12-15',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-09-15', config('app.timezone')));

    expect(AcademicCalendarPeriodResolver::currentSemesterSlugForYear('2026', AcademicCalendarTypeEnum::SEMESTER))->toBe('semester-2')
        ->and(AcademicCalendarPeriodResolver::currentSemesterSlugForYear('2025', AcademicCalendarTypeEnum::SEMESTER))->toBe('semester-1');
});

test('currentSemesterIdForYear maps the current slug to the matching semester row', function (): void {
    $semesterOne = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwo = Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-09-15', config('app.timezone')));

    expect(AcademicCalendarPeriodResolver::currentSemesterIdForYear('2026', AcademicCalendarTypeEnum::SEMESTER))->toBe($semesterTwo->id);

    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    expect(AcademicCalendarPeriodResolver::currentSemesterIdForYear('2026', AcademicCalendarTypeEnum::SEMESTER))->toBe($semesterOne->id);
});
