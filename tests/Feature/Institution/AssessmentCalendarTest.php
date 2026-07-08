<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Acl\Permission;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\ModeOfStudy;
use App\Models\Users\User;
use Carbon\Carbon;

function createAcademicCalendarForYear(
    AcademicCalendarTypeEnum $type,
    int $ordinal,
    ?string $year = null,
): AcademicCalendar {
    $year ??= (string) now()->year;
    $openingDate = now()->startOfYear()->addMonths($ordinal - 1);

    return AcademicCalendar::query()->create([
        'calendar_year' => $year,
        'type' => $type->value,
        'opening_date' => $openingDate->toDateString(),
        'closing_date' => $openingDate->copy()->addMonths(3)->toDateString(),
    ]);
}

function grantCreateAssessmentCalendarPermission(User $user): void
{
    Permission::findOrCreate('create:assessment-calendar', 'web');
    $user->givePermissionTo('create:assessment-calendar');
}

function grantUpdateAssessmentCalendarPermission(User $user): void
{
    Permission::findOrCreate('update:assessment-calendar', 'web');
    $user->givePermissionTo('update:assessment-calendar');
}

function assessmentCalendarPayload(AcademicCalendar $academicCalendar, AcademicCalendarTypeEnum $type): array
{
    $openingDate = Carbon::parse($academicCalendar->opening_date);

    return [
        'academic_calendar_id' => $academicCalendar->id,
        'start_date' => $openingDate->toDateString(),
        'end_date' => $openingDate->copy()->addWeek()->toDateString(),
        'type' => $type->value,
    ];
}

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

test('store rejects a third semester assessment calendar in the same year', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    grantCreateAssessmentCalendarPermission($user);

    $firstCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 1);
    $secondCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 2);
    $thirdCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 3);

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($firstCalendar, AcademicCalendarTypeEnum::SEMESTER),
        )
        ->assertSuccessful();

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($secondCalendar, AcademicCalendarTypeEnum::SEMESTER),
        )
        ->assertSuccessful();

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($thirdCalendar, AcademicCalendarTypeEnum::SEMESTER),
        )
        ->assertSessionHasErrors('type');
});

test('store allows two semester assessment calendars in the same year', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    grantCreateAssessmentCalendarPermission($user);

    $firstCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 1);
    $secondCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 2);

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($firstCalendar, AcademicCalendarTypeEnum::SEMESTER),
        )
        ->assertSuccessful();

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($secondCalendar, AcademicCalendarTypeEnum::SEMESTER),
        )
        ->assertSuccessful();

    expect(
        AssessmentCalendar::query()
            ->where('assessment_type_id', $assessmentType->id)
            ->where('type', AcademicCalendarTypeEnum::SEMESTER->value)
            ->count()
    )->toBe(2);
});

test('store rejects duplicate academic calendar for the same assessment type', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    grantCreateAssessmentCalendarPermission($user);

    $academicCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 1);

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($academicCalendar, AcademicCalendarTypeEnum::SEMESTER),
        )
        ->assertSuccessful();

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($academicCalendar, AcademicCalendarTypeEnum::SEMESTER),
        )
        ->assertSessionHasErrors('academic_calendar_id');
});

test('store rejects assessment calendar type mismatch with academic calendar', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    grantCreateAssessmentCalendarPermission($user);

    $academicCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::TERM, 1);

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($academicCalendar, AcademicCalendarTypeEnum::SEMESTER),
        )
        ->assertSessionHasErrors('type');
});

test('update allows editing an existing record at the year limit without counting against itself', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    grantUpdateAssessmentCalendarPermission($user);

    $firstCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 1);
    $secondCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 2);

    $firstRecord = AssessmentCalendar::factory()->create([
        'tenant_id' => $user->tenant_id,
        'assessment_type_id' => $assessmentType->id,
        'academic_calendar_id' => $firstCalendar->id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);

    AssessmentCalendar::factory()->create([
        'tenant_id' => $user->tenant_id,
        'assessment_type_id' => $assessmentType->id,
        'academic_calendar_id' => $secondCalendar->id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);

    $this->actingAs($user)
        ->put(route('assessment-calendars.update', [
            'assessment_type' => $assessmentType->id,
            'calendar' => $firstRecord->id,
        ]), [
            'academic_calendar_id' => $firstCalendar->id,
            'start_date' => Carbon::parse($firstCalendar->opening_date)->addDay()->toDateString(),
            'end_date' => Carbon::parse($firstCalendar->opening_date)->addWeeks(2)->toDateString(),
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        ])
        ->assertSuccessful();

    expect($firstRecord->refresh()->start_date->toDateString())->toBe(
        Carbon::parse($firstCalendar->opening_date)->addDay()->toDateString()
    );
});

test('store rejects a fourth term assessment calendar in the same year', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    grantCreateAssessmentCalendarPermission($user);

    foreach ([1, 2, 3] as $ordinal) {
        $academicCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::TERM, $ordinal);

        $this->actingAs($user)
            ->post(
                route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
                assessmentCalendarPayload($academicCalendar, AcademicCalendarTypeEnum::TERM),
            )
            ->assertSuccessful();
    }

    $fourthCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::TERM, 4);

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($fourthCalendar, AcademicCalendarTypeEnum::TERM),
        )
        ->assertSessionHasErrors('type');
});

test('store rejects a fifth abma assessment calendar in the same year', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    grantCreateAssessmentCalendarPermission($user);

    foreach ([1, 2, 3, 4] as $ordinal) {
        $academicCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::ABMA, $ordinal);

        $this->actingAs($user)
            ->post(
                route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
                assessmentCalendarPayload($academicCalendar, AcademicCalendarTypeEnum::ABMA),
            )
            ->assertSuccessful();
    }

    $fifthCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::ABMA, 5);

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($fifthCalendar, AcademicCalendarTypeEnum::ABMA),
        )
        ->assertSessionHasErrors('type');
});

test('store rejects start date before academic calendar opening date', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    grantCreateAssessmentCalendarPermission($user);

    $academicCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 1);

    $this->actingAs($user)
        ->post(route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]), [
            'academic_calendar_id' => $academicCalendar->id,
            'start_date' => Carbon::parse($academicCalendar->opening_date)->subDay()->toDateString(),
            'end_date' => Carbon::parse($academicCalendar->opening_date)->addWeek()->toDateString(),
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        ])
        ->assertSessionHasErrors('start_date');
});

test('store rejects end date after academic calendar closing date', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    grantCreateAssessmentCalendarPermission($user);

    $academicCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 1);

    $this->actingAs($user)
        ->post(route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]), [
            'academic_calendar_id' => $academicCalendar->id,
            'start_date' => Carbon::parse($academicCalendar->opening_date)->toDateString(),
            'end_date' => Carbon::parse($academicCalendar->closing_date)->addDay()->toDateString(),
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        ])
        ->assertSessionHasErrors('end_date');
});

test('store accepts assessment dates within academic calendar range', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    grantCreateAssessmentCalendarPermission($user);

    $academicCalendar = createAcademicCalendarForYear(AcademicCalendarTypeEnum::SEMESTER, 1);

    $this->actingAs($user)
        ->post(
            route('assessment-calendars.store', ['assessment_type' => $assessmentType->id]),
            assessmentCalendarPayload($academicCalendar, AcademicCalendarTypeEnum::SEMESTER),
        )
        ->assertSuccessful();
});
