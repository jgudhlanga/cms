<?php

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
    ]);
});
