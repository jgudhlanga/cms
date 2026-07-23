<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Acl\Permission;
use App\Models\Institution\IntakePeriod;
use App\Models\Users\User;
use Database\Seeders\Institution\ContinuousIntakePeriodSeeder;

function intakePeriodSettingsUser(int $tenantId): User
{
    $user = User::factory()->create(['tenant_id' => $tenantId]);

    Permission::findOrCreate('create:settings', 'web');
    Permission::findOrCreate('update:institution-settings', 'web');
    $user->givePermissionTo(['create:settings', 'update:institution-settings']);

    return $user;
}

function intakePeriodPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Intake '.uniqid(),
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
        'description' => 'Test intake',
        'status' => IntakePeriodStatusEnum::Open->value,
        'is_continuous' => false,
    ], $overrides);
}

test('creating a second open regular intake period fails validation', function () {
    $existing = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    $user = intakePeriodSettingsUser((int) $existing->tenant_id);

    $this->actingAs($user)
        ->post(route('intake-periods.store'), intakePeriodPayload([
            'name' => 'Second Open Regular',
        ]))
        ->assertSessionHasErrors(['status' => __('trans.intake_period_only_one_open_regular')]);
});

test('creating a second open continuous intake period fails validation', function () {
    $existing = ensureContinuousIntakeOpen();
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $user = intakePeriodSettingsUser((int) $existing->tenant_id);

    $this->actingAs($user)
        ->post(route('intake-periods.store'), intakePeriodPayload([
            'name' => 'Second Open Continuous',
            'is_continuous' => true,
        ]))
        ->assertSessionHasErrors(['is_continuous' => __('trans.intake_period_only_one_open_continuous')]);
});

test('creating an open continuous intake fails when a regular intake is open', function () {
    $regular = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    $user = intakePeriodSettingsUser((int) $regular->tenant_id);

    $this->actingAs($user)
        ->post(route('intake-periods.store'), intakePeriodPayload([
            'name' => 'Blocked Continuous',
            'is_continuous' => true,
        ]))
        ->assertSessionHasErrors(['status' => __('trans.intake_period_continuous_blocked_by_open_regular')]);
});

test('creating an open regular intake suspends open continuous intakes', function () {
    $continuous = ensureContinuousIntakeOpen();
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $user = intakePeriodSettingsUser((int) $continuous->tenant_id);

    $this->actingAs($user)
        ->post(route('intake-periods.store'), intakePeriodPayload([
            'name' => 'New Open Regular',
        ]))
        ->assertSuccessful();

    expect($continuous->fresh()->status)->toBe(IntakePeriodStatusEnum::Suspended)
        ->and(
            IntakePeriod::query()
                ->regular()
                ->where('name', 'New Open Regular')
                ->where('status', IntakePeriodStatusEnum::Open)
                ->exists()
        )->toBeTrue();
});

test('updating a regular intake to open suspends open continuous intakes', function () {
    $continuous = ensureContinuousIntakeOpen();
    $regular = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Suspended->value);
    $user = intakePeriodSettingsUser((int) $regular->tenant_id);

    $this->actingAs($user)
        ->put(route('intake-periods.update', $regular->id), intakePeriodPayload([
            'name' => $regular->name,
            'start_date' => $regular->start_date,
            'end_date' => $regular->end_date,
            'description' => $regular->description,
            'status' => IntakePeriodStatusEnum::Open->value,
            'is_continuous' => false,
        ]))
        ->assertSuccessful();

    expect($regular->fresh()->status)->toBe(IntakePeriodStatusEnum::Open)
        ->and($continuous->fresh()->status)->toBe(IntakePeriodStatusEnum::Suspended);
});

test('suspending the open regular intake reopens suspended continuous', function () {
    $continuous = ensureContinuousIntakeOpen();
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $user = intakePeriodSettingsUser((int) $continuous->tenant_id);

    $this->actingAs($user)
        ->post(route('intake-periods.store'), intakePeriodPayload([
            'name' => 'Open Regular To Suspend',
        ]))
        ->assertSuccessful();

    expect($continuous->fresh()->status)->toBe(IntakePeriodStatusEnum::Suspended);

    $regular = IntakePeriod::query()->regular()->where('name', 'Open Regular To Suspend')->firstOrFail();

    $this->actingAs($user)
        ->put(route('intake-periods.update', $regular->id), intakePeriodPayload([
            'name' => $regular->name,
            'start_date' => $regular->start_date,
            'end_date' => $regular->end_date,
            'description' => $regular->description,
            'status' => IntakePeriodStatusEnum::Suspended->value,
            'is_continuous' => false,
        ]))
        ->assertSuccessful();

    expect($regular->fresh()->status)->toBe(IntakePeriodStatusEnum::Suspended)
        ->and($continuous->fresh()->status)->toBe(IntakePeriodStatusEnum::Open);
});

test('closing the open regular intake reopens suspended continuous', function () {
    $continuous = ensureContinuousIntakeOpen();
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $user = intakePeriodSettingsUser((int) $continuous->tenant_id);

    $this->actingAs($user)
        ->post(route('intake-periods.store'), intakePeriodPayload([
            'name' => 'Open Regular To Close',
        ]))
        ->assertSuccessful();

    $regular = IntakePeriod::query()->regular()->where('name', 'Open Regular To Close')->firstOrFail();

    $this->actingAs($user)
        ->put(route('intake-periods.update', $regular->id), intakePeriodPayload([
            'name' => $regular->name,
            'start_date' => $regular->start_date,
            'end_date' => $regular->end_date,
            'description' => $regular->description,
            'status' => IntakePeriodStatusEnum::Closed->value,
            'is_continuous' => false,
        ]))
        ->assertSuccessful();

    expect($regular->fresh()->status)->toBe(IntakePeriodStatusEnum::Closed)
        ->and($continuous->fresh()->status)->toBe(IntakePeriodStatusEnum::Open);
});

test('creating suspended or closed intakes does not trigger open uniqueness', function () {
    $existing = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    $user = intakePeriodSettingsUser((int) $existing->tenant_id);

    $this->actingAs($user)
        ->post(route('intake-periods.store'), intakePeriodPayload([
            'name' => 'Suspended Regular',
            'status' => IntakePeriodStatusEnum::Suspended->value,
        ]))
        ->assertSuccessful();

    $this->actingAs($user)
        ->post(route('intake-periods.store'), intakePeriodPayload([
            'name' => 'Closed Continuous',
            'status' => IntakePeriodStatusEnum::Closed->value,
            'is_continuous' => true,
        ]))
        ->assertSuccessful();
});

test('continuous intake period seeder uses current calendar year bounds', function () {
    IntakePeriod::query()->continuous()->delete();

    (new ContinuousIntakePeriodSeeder)->run();

    $continuous = IntakePeriod::query()
        ->where('tenant_id', TenantEnum::HARARE_POLY->id())
        ->continuous()
        ->where('is_active', true)
        ->first();

    expect($continuous)->not->toBeNull()
        ->and((string) $continuous->start_date)->toBe(now()->startOfYear()->toDateString())
        ->and((string) $continuous->end_date)->toBe(now()->endOfYear()->toDateString());
});
