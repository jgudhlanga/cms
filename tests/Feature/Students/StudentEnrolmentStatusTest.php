<?php

use App\Models\Rbac\Permission;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;

test('guests are redirected when visiting student enrolment statuses page', function () {
    $this->get(route('student-enrolment-statuses.index'))->assertRedirect('/login');
});

test('authenticated users with permission can view student enrolment statuses page', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('viewAny:student-enrolment-statuses', 'web');
    $user->givePermissionTo('viewAny:student-enrolment-statuses');

    $this->actingAs($user)
        ->get(route('student-enrolment-statuses.index'))
        ->assertSuccessful();
});

test('store requires create student enrolment statuses permission', function () {
    $user = User::factory()->create();
    $payload = [
        'name' => 'On Leave',
        'description' => 'Temporary study break',
    ];

    $this->actingAs($user)
        ->post(route('student-enrolment-statuses.store'), $payload)
        ->assertForbidden();

    Permission::findOrCreate('create:student-enrolment-statuses', 'web');
    $user->givePermissionTo('create:student-enrolment-statuses');

    $this->actingAs($user)
        ->post(route('student-enrolment-statuses.store'), $payload)
        ->assertSuccessful();

    $record = StudentEnrolmentStatus::query()->latest('id')->first();
    expect($record)->not->toBeNull()
        ->and($record->name)->toBe('On Leave');
});

test('update requires update student enrolment statuses permission', function () {
    $user = User::factory()->create();
    $status = StudentEnrolmentStatus::query()->create([
        'name' => 'Active',
        'description' => 'Current',
    ]);

    $payload = [
        'name' => 'Active Updated',
        'description' => 'Current and verified',
    ];

    $this->actingAs($user)
        ->put(route('student-enrolment-statuses.update', $status->id), $payload)
        ->assertForbidden();

    Permission::findOrCreate('update:student-enrolment-statuses', 'web');
    $user->givePermissionTo('update:student-enrolment-statuses');

    $this->actingAs($user)
        ->put(route('student-enrolment-statuses.update', $status->id), $payload)
        ->assertSuccessful();

    expect($status->refresh()->name)->toBe('Active Updated');
});

test('archive requires delete student enrolment statuses permission', function () {
    $user = User::factory()->create();
    $status = StudentEnrolmentStatus::query()->create([
        'name' => 'Archive Me',
        'description' => 'For archive testing',
    ]);

    $this->actingAs($user)
        ->delete(route('student-enrolment-statuses.destroy', $status->id))
        ->assertForbidden();

    Permission::findOrCreate('delete:student-enrolment-statuses', 'web');
    $user->givePermissionTo('delete:student-enrolment-statuses');

    $this->actingAs($user)
        ->delete(route('student-enrolment-statuses.destroy', $status->id))
        ->assertSuccessful();

    expect($status->refresh()->deleted_at)->not->toBeNull();
});

test('restore requires restore student enrolment statuses permission', function () {
    $user = User::factory()->create();
    $status = StudentEnrolmentStatus::query()->create([
        'name' => 'Restore Me',
        'description' => 'For restore testing',
    ]);
    $status->delete();

    $this->actingAs($user)
        ->put(route('student-enrolment-statuses.restore', $status->id))
        ->assertForbidden();

    Permission::findOrCreate('restore:student-enrolment-statuses', 'web');
    $user->givePermissionTo('restore:student-enrolment-statuses');

    $this->actingAs($user)
        ->put(route('student-enrolment-statuses.restore', $status->id))
        ->assertSuccessful();

    expect($status->fresh()->deleted_at)->toBeNull();
});

test('force delete requires force delete student enrolment statuses permission', function () {
    $user = User::factory()->create();
    $status = StudentEnrolmentStatus::query()->create([
        'name' => 'Delete Me',
        'description' => 'For force delete testing',
    ]);

    $this->actingAs($user)
        ->delete(route('student-enrolment-statuses.force-delete', $status->id))
        ->assertForbidden();

    Permission::findOrCreate('forceDelete:student-enrolment-statuses', 'web');
    $user->givePermissionTo('forceDelete:student-enrolment-statuses');

    $this->actingAs($user)
        ->delete(route('student-enrolment-statuses.force-delete', $status->id))
        ->assertSuccessful();

    expect(StudentEnrolmentStatus::query()->find($status->id))->toBeNull();
});

test('student enrolment statuses dropdown api returns data', function () {
    StudentEnrolmentStatus::query()->create([
        'name' => 'API Status',
        'description' => 'Available in dropdown API',
    ]);

    $this->get(route('v1.student-enrolment-statuses.index'))
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'API Status']);
});
