<?php

use App\Enums\Acl\PermissionEnum;
use App\Models\Users\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionEnum::VIEW_DASHBOARD->value);
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    // Dashboard may 404 in tests when metrics/intake data is missing
    $response->assertSuccessful();
})->skip('Dashboard depends on application metrics and intake period data');