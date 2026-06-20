<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;

beforeEach(function () {
    enableDashboardModule();
});

test('dashboard header uses live academic calendar context', function () {
    $this->travelTo('2026-09-15');

    $user = userWithDashboardPermission();

    $semesterOne = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    $semesterTwo = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('academicCalendar.id', $semesterTwo->id)
            ->where('academicContextSubtitle', 'Ministry of Higher & Tertiary Education · Academic Year 2026 · Semester 2 (Jul – Dec 2026)')
            ->where('appEnv', 'testing')
        );
});

test('dashboard respects selected academic calendar query parameter', function () {
    $this->travelTo('2026-09-15');

    $user = userWithDashboardPermission();

    $semesterOne = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user).'&academic_calendar_id='.$semesterOne->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('academicCalendar.id', $semesterOne->id)
            ->where('academicContextSubtitle', 'Ministry of Higher & Tertiary Education · Academic Year 2026 · Semester 1 (Jan – Apr 2026)')
        );
});

test('dashboard resolves upcoming semester when today falls between semester periods', function () {
    $this->travelTo('2026-05-15');

    $user = userWithDashboardPermission();

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    $semesterTwo = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('academicCalendar.id', $semesterTwo->id)
            ->where('academicContextSubtitle', 'Ministry of Higher & Tertiary Education · Academic Year 2026 · Semester 2 (Jul – Dec 2026)')
        );
});

test('dashboard ignores non-semester calendars when resolving current period', function () {
    $this->travelTo('2026-09-15');

    $user = userWithDashboardPermission();

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::TERM->value,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    $semesterTwo = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('academicCalendar.id', $semesterTwo->id)
        );
});
