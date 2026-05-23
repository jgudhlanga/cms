<?php

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('json api hostels index filters by type male', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $tenantId = TenantEnum::HARARE_POLY->id();

    Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Boys Block '.uniqid(),
        'location' => 'North',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'male',
    ]);

    Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Girls Block '.uniqid(),
        'location' => 'South',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'female',
    ]);

    $response = $this
        ->jsonApi('hostels')
        ->get(route('v1.json.hms.hostels.index', ['filter' => ['type' => 'male']]));

    $response->assertSuccessful();

    $types = collect($response->json('data'))->pluck('attributes.type')->unique()->values()->all();

    expect($types)->toBe(['male']);
});

test('json api hostels index filters by type female case insensitively', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $tenantId = TenantEnum::HARARE_POLY->id();

    Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Girls Only '.uniqid(),
        'location' => 'East',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'female',
    ]);

    $response = $this
        ->jsonApi('hostels')
        ->get(route('v1.json.hms.hostels.index', ['filter' => ['type' => 'Female']]));

    $response->assertSuccessful();

    expect(collect($response->json('data'))->pluck('attributes.type')->unique()->values()->all())
        ->toBe(['female']);
});
