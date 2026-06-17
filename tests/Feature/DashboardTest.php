<?php

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    enableDashboardModule();

    $user = userWithDashboardPermission();

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful();
});
