<?php

use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Acl\Permission;
use App\Models\Users\User;

test('guests are redirected when visiting academic year options page', function () {
    $this->get(route('academic-year-options.index'))->assertRedirect('/login');
});

test('authenticated users with permission can view academic year options page', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('view:settings', 'web');
    $user->givePermissionTo('view:settings');

    $this->actingAs($user)
        ->get(route('academic-year-options.index'))
        ->assertSuccessful();
});

test('store requires create settings permission', function () {
    $user = User::factory()->create();
    $payload = [
        'name' => 'Semester 1',
        'description' => 'First semester',
    ];

    $this->actingAs($user)
        ->post(route('academic-year-options.store'), $payload)
        ->assertForbidden();

    Permission::findOrCreate('create:settings', 'web');
    $user->givePermissionTo('create:settings');

    $this->actingAs($user)
        ->post(route('academic-year-options.store'), $payload)
        ->assertSuccessful();

    $record = AcademicYearOption::query()->latest('id')->first();
    expect($record)->not->toBeNull()
        ->and($record->name)->toBe('Semester 1');
});

test('update requires update settings permission', function () {
    $user = User::factory()->create();
    $academicYearOption = AcademicYearOption::query()->create([
        'name' => 'Term 1',
        'description' => 'Initial description',
        'slug' => 'term-1',
    ]);

    $payload = [
        'name' => 'Term 1 Updated',
        'description' => 'Updated description',
    ];

    $this->actingAs($user)
        ->put(route('academic-year-options.update', $academicYearOption->id), $payload)
        ->assertForbidden();

    Permission::findOrCreate('update:settings', 'web');
    $user->givePermissionTo('update:settings');

    $this->actingAs($user)
        ->put(route('academic-year-options.update', $academicYearOption->id), $payload)
        ->assertSuccessful();

    expect($academicYearOption->refresh()->name)->toBe('Term 1 Updated');
});

test('archive requires delete settings permission', function () {
    $user = User::factory()->create();
    $academicYearOption = AcademicYearOption::query()->create([
        'name' => 'Term 2',
        'description' => 'Archive test',
        'slug' => 'term-2',
    ]);

    $this->actingAs($user)
        ->delete(route('academic-year-options.destroy', $academicYearOption->id))
        ->assertForbidden();

    Permission::findOrCreate('delete:settings', 'web');
    $user->givePermissionTo('delete:settings');

    $this->actingAs($user)
        ->delete(route('academic-year-options.destroy', $academicYearOption->id))
        ->assertSuccessful();

    expect($academicYearOption->refresh()->deleted_at)->not->toBeNull();
});

test('restore requires restore settings permission', function () {
    $user = User::factory()->create();
    $academicYearOption = AcademicYearOption::query()->create([
        'name' => 'Term 3',
        'description' => 'Restore test',
        'slug' => 'term-3',
    ]);
    $academicYearOption->delete();

    $this->actingAs($user)
        ->put(route('academic-year-options.restore', $academicYearOption->id))
        ->assertForbidden();

    Permission::findOrCreate('restore:settings', 'web');
    $user->givePermissionTo('restore:settings');

    $this->actingAs($user)
        ->put(route('academic-year-options.restore', $academicYearOption->id))
        ->assertSuccessful();

    expect($academicYearOption->fresh()->deleted_at)->toBeNull();
});

test('force delete requires force delete settings permission', function () {
    $user = User::factory()->create();
    $academicYearOption = AcademicYearOption::query()->create([
        'name' => 'Term 4',
        'description' => 'Force delete test',
        'slug' => 'term-4',
    ]);

    $this->actingAs($user)
        ->delete(route('academic-year-options.force-delete', $academicYearOption->id))
        ->assertForbidden();

    Permission::findOrCreate('forceDelete:settings', 'web');
    $user->givePermissionTo('forceDelete:settings');

    $this->actingAs($user)
        ->delete(route('academic-year-options.force-delete', $academicYearOption->id))
        ->assertSuccessful();

    expect(AcademicYearOption::query()->find($academicYearOption->id))->toBeNull();
});

test('academic year options dropdown api returns data', function () {
    AcademicYearOption::query()->create([
        'name' => 'Semester 2',
        'description' => 'Available in dropdown API',
        'slug' => 'semester-2',
    ]);

    $this->get(route('v1.academic-year-options.index'))
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'Semester 2']);
});
