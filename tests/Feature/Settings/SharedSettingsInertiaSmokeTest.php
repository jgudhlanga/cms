<?php

use App\Models\Rbac\Permission;
use App\Models\Users\User;

function settingsSmokeUser(): User
{
    $user = User::factory()->create();
    Permission::findOrCreate('view:settings', 'web');
    $user->givePermissionTo(['view:settings']);

    return $user;
}

test('payment settings index renders the shared payments page', function () {
    $user = settingsSmokeUser();

    $this->actingAs($user)
        ->get(route('payments-index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('shared/payments/Index'));
});

test('academic levels index renders for authorized users', function () {
    $user = settingsSmokeUser();

    $this->actingAs($user)
        ->get(route('academic-levels.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('shared/academicLevels/Index')
            ->has('academicLevels')
            ->has('filters')
            ->has('trashedCount')
        );
});
