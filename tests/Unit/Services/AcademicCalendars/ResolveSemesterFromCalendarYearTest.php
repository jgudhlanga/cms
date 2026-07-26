<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Services\AcademicCalendars\ResolveSemesterFromCalendarYear;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('resolves semester two when today falls in the second semester window', function () {
    Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwoId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    )->id;

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-10',
        'closing_date' => '2026-06-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-20',
    ]);

    $ref = Carbon::parse('2026-08-10');
    $service = new ResolveSemesterFromCalendarYear($ref);

    expect($service->resolveForCalendarType('2026', AcademicCalendarTypeEnum::SEMESTER))->toBe($semesterTwoId);
});

test('resolves abma option from opening quarter when today is inside the period', function () {
    $abma2Id = (int) Semester::query()->firstOrCreate(
        ['slug' => 'abma-2'],
        ['name' => 'ABMA 2', 'description' => null],
    )->id;

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::ABMA,
        'opening_date' => '2026-04-01',
        'closing_date' => '2026-06-15',
    ]);

    $ref = Carbon::parse('2026-05-01');
    $service = new ResolveSemesterFromCalendarYear($ref);

    expect($service->resolveForCalendarType('2026', AcademicCalendarTypeEnum::ABMA))->toBe($abma2Id);
});

test('returns null when today is outside all calendar periods', function () {
    Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $ref = Carbon::parse('2026-08-15');
    $service = new ResolveSemesterFromCalendarYear($ref);

    expect($service->resolveForCalendarType('2026', AcademicCalendarTypeEnum::SEMESTER))->toBeNull();
});

test('when multiple periods contain today picks the row with the latest opening date', function () {
    Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwoId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    )->id;

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-31',
    ]);

    $ref = Carbon::parse('2026-08-01');
    $service = new ResolveSemesterFromCalendarYear($ref);

    expect($service->resolveForCalendarType('2026', AcademicCalendarTypeEnum::SEMESTER))->toBe($semesterTwoId);
});

test('returns null when academic year option row is missing for computed slug', function () {
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $ref = Carbon::parse('2026-05-15');
    $service = new ResolveSemesterFromCalendarYear($ref);

    expect($service->resolveForCalendarType('2026', AcademicCalendarTypeEnum::SEMESTER))->toBeNull();
});

test('resolveSemesterId delegates to semester calendar type', function () {
    $semesterOneId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    )->id;

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $ref = Carbon::parse('2026-05-15');
    $service = new ResolveSemesterFromCalendarYear($ref);

    expect($service->resolveSemesterId('2026'))->toBe($semesterOneId);
});
