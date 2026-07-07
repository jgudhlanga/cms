<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Acl\Permission;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\ModeOfStudy;
use App\Models\Users\User;

test('guests are redirected when visiting assessment calendars page', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $this->get(route('assessment-calendars.index', ['assessment_type' => $assessmentType->id]))
        ->assertRedirect('/login');
});

test('authenticated users with permission can view assessment calendars page', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    Permission::findOrCreate('viewAny:assessment-calendar', 'web');
    $user->givePermissionTo('viewAny:assessment-calendar');

    $this->actingAs($user)
        ->get(route('assessment-calendars.index', ['assessment_type' => $assessmentType->id]))
        ->assertSuccessful();
});

test('store requires create assessment calendar permission', function () {
    $user = User::factory()->create();
    $modeOfStudy = ModeOfStudy::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
        'modes_of_study' => [$modeOfStudy->id],
    ]);

    $academicCalendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => now()->subMonth()->toDateString(),
        'closing_date' => now()->addMonths(5)->toDateString(),
    ]);

    $payload = [
        'academic_calendar_id' => $academicCalendar->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ];

    $this->actingAs($user)
        ->post(route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]), $payload)
        ->assertForbidden();

    Permission::findOrCreate('create:assessment-calendar', 'web');
    $user->givePermissionTo('create:assessment-calendar');

    $this->actingAs($user)
        ->post(route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]), $payload)
        ->assertSuccessful();

    $record = AssessmentCalendar::query()->latest('id')->first();
    expect($record)->not->toBeNull()
        ->and($record->assessment_type_id)->toBe($assessmentType->id)
        ->and($record->academic_calendar_id)->toBe($academicCalendar->id);
});

test('update requires update assessment calendar permission', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $academicCalendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::TERM->value,
        'opening_date' => now()->subMonth()->toDateString(),
        'closing_date' => now()->addMonths(5)->toDateString(),
    ]);

    $assessmentCalendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $user->tenant_id,
        'assessment_type_id' => $assessmentType->id,
        'academic_calendar_id' => $academicCalendar->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addWeeks(2)->toDateString(),
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);

    $payload = [
        'academic_calendar_id' => $academicCalendar->id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addMonths(2)->toDateString(),
        'type' => AcademicCalendarTypeEnum::TERM->value,
    ];

    $this->actingAs($user)
        ->put(route('assessment-calendars.update', [
            'assessment_type' => $assessmentType->id,
            'calendar' => $assessmentCalendar->id,
        ]), $payload)
        ->assertForbidden();

    Permission::findOrCreate('update:assessment-calendar', 'web');
    $user->givePermissionTo('update:assessment-calendar');

    $this->actingAs($user)
        ->put(route('assessment-calendars.update', [
            'assessment_type' => $assessmentType->id,
            'calendar' => $assessmentCalendar->id,
        ]), $payload)
        ->assertSuccessful();

    expect($assessmentCalendar->refresh()->type)->toBe(AcademicCalendarTypeEnum::TERM);
});

test('archive requires delete assessment calendar permission', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $assessmentCalendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $user->tenant_id,
        'assessment_type_id' => $assessmentType->id,
    ]);

    $this->actingAs($user)
        ->delete(route('assessment-calendars.destroy', [
            'assessment_type' => $assessmentType->id,
            'calendar' => $assessmentCalendar->id,
        ]))
        ->assertForbidden();

    Permission::findOrCreate('delete:assessment-calendar', 'web');
    $user->givePermissionTo('delete:assessment-calendar');

    $this->actingAs($user)
        ->delete(route('assessment-calendars.destroy', [
            'assessment_type' => $assessmentType->id,
            'calendar' => $assessmentCalendar->id,
        ]))
        ->assertSuccessful();

    expect($assessmentCalendar->refresh()->deleted_at)->not->toBeNull();
});

test('restore requires restore assessment calendar permission', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $assessmentCalendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $user->tenant_id,
        'assessment_type_id' => $assessmentType->id,
    ]);
    $assessmentCalendar->delete();

    $this->actingAs($user)
        ->put(route('assessment-calendars.restore', [
            'assessment_type' => $assessmentType->id,
            'calendar' => $assessmentCalendar->id,
        ]))
        ->assertForbidden();

    Permission::findOrCreate('restore:assessment-calendar', 'web');
    $user->givePermissionTo('restore:assessment-calendar');

    $this->actingAs($user)
        ->put(route('assessment-calendars.restore', [
            'assessment_type' => $assessmentType->id,
            'calendar' => $assessmentCalendar->id,
        ]))
        ->assertSuccessful();

    expect($assessmentCalendar->fresh()->deleted_at)->toBeNull();
});

test('force delete requires force delete assessment calendar permission', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $assessmentCalendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $user->tenant_id,
        'assessment_type_id' => $assessmentType->id,
    ]);

    $this->actingAs($user)
        ->delete(route('assessment-calendars.force-delete', [
            'assessment_type' => $assessmentType->id,
            'calendar' => $assessmentCalendar->id,
        ]))
        ->assertForbidden();

    Permission::findOrCreate('forceDelete:assessment-calendar', 'web');
    $user->givePermissionTo('forceDelete:assessment-calendar');

    $this->actingAs($user)
        ->delete(route('assessment-calendars.force-delete', [
            'assessment_type' => $assessmentType->id,
            'calendar' => $assessmentCalendar->id,
        ]))
        ->assertSuccessful();

    expect(AssessmentCalendar::query()->find($assessmentCalendar->id))->toBeNull();
});

test('store validates end date is on or after start date', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $academicCalendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => now()->subMonth()->toDateString(),
        'closing_date' => now()->addMonths(5)->toDateString(),
    ]);

    Permission::findOrCreate('create:assessment-calendar', 'web');
    $user->givePermissionTo('create:assessment-calendar');

    $this->actingAs($user)
        ->post(route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]), [
            'academic_calendar_id' => $academicCalendar->id,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->toDateString(),
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        ])
        ->assertSessionHasErrors('end_date');
});

test('assessment calendars are scoped to parent assessment type', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);
    $otherAssessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $calendarForType = AssessmentCalendar::factory()->create([
        'tenant_id' => $user->tenant_id,
        'assessment_type_id' => $assessmentType->id,
    ]);

    AssessmentCalendar::factory()->create([
        'tenant_id' => $user->tenant_id,
        'assessment_type_id' => $otherAssessmentType->id,
    ]);

    Permission::findOrCreate('viewAny:assessment-calendar', 'web');
    $user->givePermissionTo('viewAny:assessment-calendar');

    $response = $this->actingAs($user)
        ->get(route('assessment-calendars.index', ['assessment_type' => $assessmentType->id]))
        ->assertSuccessful();

    $ids = collect($response->viewData('page')['props']['assessmentCalendars']['data'] ?? [])
        ->pluck('id')
        ->map(fn ($id) => (string) $id)
        ->all();

    expect($ids)->toContain((string) $calendarForType->id)
        ->and($ids)->toHaveCount(1);
});
