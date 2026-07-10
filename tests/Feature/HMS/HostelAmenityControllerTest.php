<?php

use App\Models\HMS\HostelAmenity;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;

it('forbids creating hostel amenities without permission', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->post(route('hostel-amenities.store'), [
            'name' => 'Desk',
        ])
        ->assertForbidden();
});

it('allows creating hostel amenities with permission', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('create:hostel-amenities');

    $response = $this->actingAs($user)->post(route('hostel-amenities.store'), [
        'name' => 'Desk',
    ]);

    expect($response->status())->toBeLessThan(400);

    $this->assertDatabaseHas('hostel_amenities', [
        'tenant_id' => $tenant->id,
        'name' => 'Desk',
        'slug' => 'desk',
        'market_value' => null,
    ]);
});

it('allows creating hostel amenities with market value', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('create:hostel-amenities');

    $response = $this->actingAs($user)->post(route('hostel-amenities.store'), [
        'name' => 'Wardrobe',
        'market_value' => 125.5,
    ]);

    expect($response->status())->toBeLessThan(400);

    $this->assertDatabaseHas('hostel_amenities', [
        'tenant_id' => $tenant->id,
        'name' => 'Wardrobe',
        'slug' => 'wardrobe',
        'market_value' => 125.5,
    ]);
});

it('allows updating hostel amenity market value', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-amenities');

    $amenity = HostelAmenity::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Mirror',
        'slug' => 'mirror',
        'market_value' => 40,
    ]);

    $response = $this->actingAs($user)->put(route('hostel-amenities.update', $amenity), [
        'name' => 'Mirror',
        'market_value' => null,
    ]);

    expect($response->status())->toBeLessThan(400);

    $this->assertDatabaseHas('hostel_amenities', [
        'id' => $amenity->id,
        'market_value' => null,
    ]);
});
