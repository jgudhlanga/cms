<?php

use App\Models\Rbac\Permission;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\ModeOfStudy;
use App\Models\Users\User;

test('guests are redirected when visiting assessment types page', function () {
    $this->get(route('assessment-types.index'))->assertRedirect('/login');
});

test('authenticated users with permission can view assessment types page', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('viewAny:assessment-types', 'web');
    $user->givePermissionTo('viewAny:assessment-types');

    $this->actingAs($user)
        ->get(route('assessment-types.index'))
        ->assertSuccessful();
});

test('store requires create assessment types permission', function () {
    $user = User::factory()->create();
    $modeOfStudy = ModeOfStudy::factory()->create();
    $payload = [
        'name' => 'Continuous Assessment',
        'modes_of_study' => [$modeOfStudy->id],
        'description' => 'CA based assessment.',
    ];

    $this->actingAs($user)
        ->post(route('assessment-types.store'), $payload)
        ->assertForbidden();

    Permission::findOrCreate('create:assessment-types', 'web');
    $user->givePermissionTo('create:assessment-types');

    $this->actingAs($user)
        ->post(route('assessment-types.store'), $payload)
        ->assertSuccessful();

    $record = AssessmentType::query()->latest('id')->first();
    expect($record)->not->toBeNull()
        ->and($record->name)->toBe('Continuous Assessment')
        ->and($record->modes_of_study)->toBe([$modeOfStudy->id]);
});

test('update requires update assessment types permission', function () {
    $user = User::factory()->create();
    $initialMode = ModeOfStudy::factory()->create();
    $nextMode = ModeOfStudy::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Exam',
        'modes_of_study' => [$initialMode->id],
    ]);

    $payload = [
        'name' => 'Updated Exam',
        'modes_of_study' => [$nextMode->id],
        'description' => 'Updated description',
    ];

    $this->actingAs($user)
        ->put(route('assessment-types.update', $assessmentType->id), $payload)
        ->assertForbidden();

    Permission::findOrCreate('update:assessment-types', 'web');
    $user->givePermissionTo('update:assessment-types');

    $this->actingAs($user)
        ->put(route('assessment-types.update', $assessmentType->id), $payload)
        ->assertSuccessful();

    expect($assessmentType->refresh()->name)->toBe('Updated Exam')
        ->and($assessmentType->modes_of_study)->toBe([$nextMode->id]);
});

test('archive requires delete assessment types permission', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $this->actingAs($user)
        ->delete(route('assessment-types.destroy', $assessmentType->id))
        ->assertForbidden();

    Permission::findOrCreate('delete:assessment-types', 'web');
    $user->givePermissionTo('delete:assessment-types');

    $this->actingAs($user)
        ->delete(route('assessment-types.destroy', $assessmentType->id))
        ->assertSuccessful();

    expect($assessmentType->refresh()->deleted_at)->not->toBeNull();
});

test('restore requires restore assessment types permission', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);
    $assessmentType->delete();

    $this->actingAs($user)
        ->put(route('assessment-types.restore', $assessmentType->id))
        ->assertForbidden();

    Permission::findOrCreate('restore:assessment-types', 'web');
    $user->givePermissionTo('restore:assessment-types');

    $this->actingAs($user)
        ->put(route('assessment-types.restore', $assessmentType->id))
        ->assertSuccessful();

    expect($assessmentType->fresh()->deleted_at)->toBeNull();
});

test('force delete requires force delete assessment types permission', function () {
    $user = User::factory()->create();
    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $this->actingAs($user)
        ->delete(route('assessment-types.force-delete', $assessmentType->id))
        ->assertForbidden();

    Permission::findOrCreate('forceDelete:assessment-types', 'web');
    $user->givePermissionTo('forceDelete:assessment-types');

    $this->actingAs($user)
        ->delete(route('assessment-types.force-delete', $assessmentType->id))
        ->assertSuccessful();

    expect(AssessmentType::query()->find($assessmentType->id))->toBeNull();
});
