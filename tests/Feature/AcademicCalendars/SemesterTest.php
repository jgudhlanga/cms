<?php

use App\Models\AcademicCalendars\Semester;
use App\Models\Rbac\Permission;
use App\Models\Users\User;
use Database\Seeders\AcademicCalendars\SemesterSeeder;

test('guests are redirected when visiting semesters page', function () {
    $this->get(route('semesters.index'))->assertRedirect('/login');
});

test('authenticated users with permission can view semesters page', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('viewAny:semesters', 'web');
    $user->givePermissionTo('viewAny:semesters');

    $this->actingAs($user)
        ->get(route('semesters.index'))
        ->assertSuccessful();
});

test('store requires create semesters permission', function () {
    $user = User::factory()->create();
    $payload = [
        'name' => 'Semester 1',
        'description' => 'First semester',
    ];

    $this->actingAs($user)
        ->post(route('semesters.store'), $payload)
        ->assertForbidden();

    Permission::findOrCreate('create:semesters', 'web');
    $user->givePermissionTo('create:semesters');

    $this->actingAs($user)
        ->post(route('semesters.store'), $payload)
        ->assertSuccessful();

    $record = Semester::query()->latest('id')->first();
    expect($record)->not->toBeNull()
        ->and($record->name)->toBe('Semester 1');
});

test('update requires update semesters permission', function () {
    $user = User::factory()->create();
    $semester = Semester::query()->create([
        'name' => 'Term 1',
        'description' => 'Initial description',
        'slug' => 'term-1',
    ]);

    $payload = [
        'name' => 'Term 1 Updated',
        'description' => 'Updated description',
    ];

    $this->actingAs($user)
        ->put(route('semesters.update', $semester->id), $payload)
        ->assertForbidden();

    Permission::findOrCreate('update:semesters', 'web');
    $user->givePermissionTo('update:semesters');

    $this->actingAs($user)
        ->put(route('semesters.update', $semester->id), $payload)
        ->assertSuccessful();

    expect($semester->refresh()->name)->toBe('Term 1 Updated');
});

test('archive requires delete semesters permission', function () {
    $user = User::factory()->create();
    $semester = Semester::query()->create([
        'name' => 'Term 2',
        'description' => 'Archive test',
        'slug' => 'term-2',
    ]);

    $this->actingAs($user)
        ->delete(route('semesters.destroy', $semester->id))
        ->assertForbidden();

    Permission::findOrCreate('delete:semesters', 'web');
    $user->givePermissionTo('delete:semesters');

    $this->actingAs($user)
        ->delete(route('semesters.destroy', $semester->id))
        ->assertSuccessful();

    expect($semester->refresh()->deleted_at)->not->toBeNull();
});

test('restore requires restore semesters permission', function () {
    $user = User::factory()->create();
    $semester = Semester::query()->create([
        'name' => 'Term 3',
        'description' => 'Restore test',
        'slug' => 'term-3',
    ]);
    $semester->delete();

    $this->actingAs($user)
        ->put(route('semesters.restore', $semester->id))
        ->assertForbidden();

    Permission::findOrCreate('restore:semesters', 'web');
    $user->givePermissionTo('restore:semesters');

    $this->actingAs($user)
        ->put(route('semesters.restore', $semester->id))
        ->assertSuccessful();

    expect($semester->fresh()->deleted_at)->toBeNull();
});

test('force delete requires force delete semesters permission', function () {
    $user = User::factory()->create();
    $semester = Semester::query()->create([
        'name' => 'Term 4',
        'description' => 'Force delete test',
        'slug' => 'term-4',
    ]);

    $this->actingAs($user)
        ->delete(route('semesters.force-delete', $semester->id))
        ->assertForbidden();

    Permission::findOrCreate('forceDelete:semesters', 'web');
    $user->givePermissionTo('forceDelete:semesters');

    $this->actingAs($user)
        ->delete(route('semesters.force-delete', $semester->id))
        ->assertSuccessful();

    expect(Semester::query()->find($semester->id))->toBeNull();
});

test('semesters dropdown api returns data', function () {
    Semester::query()->create([
        'name' => 'Semester 2',
        'description' => 'Available in dropdown API',
        'slug' => 'semester-2',
    ]);

    $this->get(route('v1.semesters.index'))
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'Semester 2']);
});

test('semesters api filters by calendar_type semester', function () {
    $this->seed(SemesterSeeder::class);
    $response = $this->getJson(route('v1.semesters.index').'?calendar_type=semester')->assertSuccessful();
    $rows = $response->json('data') ?? [];
    expect($rows)->not->toBeEmpty();
    foreach ($rows as $row) {
        expect((string) ($row['attributes']['name'] ?? ''))->toStartWith('Semester');
    }
});

test('semesters api filters by calendar_type term', function () {
    $this->seed(SemesterSeeder::class);
    $response = $this->getJson(route('v1.semesters.index').'?calendar_type=term')->assertSuccessful();
    $rows = $response->json('data') ?? [];
    expect($rows)->not->toBeEmpty();
    foreach ($rows as $row) {
        expect((string) ($row['attributes']['name'] ?? ''))->toStartWith('Term');
    }
});

test('semesters api filters by calendar_type abma', function () {
    $this->seed(SemesterSeeder::class);
    $response = $this->getJson(route('v1.semesters.index').'?calendar_type=abma')->assertSuccessful();
    $rows = $response->json('data') ?? [];
    expect($rows)->not->toBeEmpty();
    foreach ($rows as $row) {
        expect((string) ($row['attributes']['name'] ?? ''))->toStartWith('ABMA');
    }
});
