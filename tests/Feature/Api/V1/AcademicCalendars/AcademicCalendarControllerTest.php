<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('academic calendars index includes computed period name attribute', function () {
    $this->travelTo('2026-12-01');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-08-01',
        'closing_date' => '2026-11-30',
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::TERM->value,
        'opening_date' => '2026-05-10',
        'closing_date' => '2026-08-15',
    ]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.academic-calendars.index'))
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => 'Semester 1 - (15 January - 30 April 2026)',
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        ])
        ->assertJsonFragment([
            'name' => 'Semester 2 - (1 August - 30 November 2026)',
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        ])
        ->assertJsonFragment([
            'name' => 'Term 2 - (10 May - 15 August 2026)',
            'type' => AcademicCalendarTypeEnum::TERM->value,
        ]);
});

test('academic calendars index omits calendars whose opening date is still in the future', function () {
    $this->travelTo('2026-04-18');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-08-01',
        'closing_date' => '2026-11-30',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.academic-calendars.index'))->assertSuccessful();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.openingDate'))->toBe('2026-01-15');
});

test('academic calendar options returns distinct started calendar years', function () {
    $this->travelTo('2026-04-18');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-08-01',
        'closing_date' => '2026-11-30',
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2025-01-10',
        'closing_date' => '2025-06-30',
    ]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.academic-calendars.options'))
        ->assertSuccessful()
        ->assertJsonPath('data.0.academicYear', '2026')
        ->assertJsonPath('data.1.academicYear', '2025');

    expect($this->getJson(route('v1.academic-calendars.options'))->json('data'))->toHaveCount(2);
});

test('academic calendar store validates type enum values', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($user)
        ->post(route('academic-calendars.store'), [
            'calendar_year' => '2027',
            'type' => 'quarter',
            'opening_date' => '2027-01-10',
            'closing_date' => '2027-04-10',
        ])
        ->assertSessionHasErrors(['type']);
});
