<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('academic calendars index includes computed name attribute', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.academic-calendars.index'))
        ->assertSuccessful()
        ->assertJsonPath('data.0.attributes.name', '2026 (15 January - 30 April 2026)')
        ->assertJsonPath('data.0.attributes.type', AcademicCalendarTypeEnum::SEMESTER->value);
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
