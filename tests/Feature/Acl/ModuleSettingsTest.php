<?php

use App\Models\Acl\Module;
use App\Models\Acl\Permission;
use App\Models\Users\User;

function moduleSettingsUser(): User
{
    $user = User::factory()->create();
    Permission::findOrCreate('view:modules', 'web');
    Permission::findOrCreate('update:modules', 'web');
    $user->givePermissionTo(['view:modules', 'update:modules']);

    return $user;
}

test('module show page is accessible for authorized users', function () {
    $user = moduleSettingsUser();
    $module = Module::query()->where('slug', 'dashboards')->firstOrFail();

    $this->actingAs($user)
        ->get(route('modules.show', $module))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('acl/modules/Show')
            ->has('module')
        );
});

test('dashboard module settings can be updated', function () {
    $user = moduleSettingsUser();
    $module = Module::query()->where('slug', 'dashboards')->firstOrFail();

    $payload = [
        'status' => true,
        'settings' => [
            'tabs' => [
                'overview' => true,
                'academic' => false,
                'enrolments' => true,
                'attendance' => false,
                'staff' => true,
                'finance' => false,
                'hostel' => true,
            ],
        ],
    ];

    $this->actingAs($user)
        ->put(route('modules.settings', $module), $payload)
        ->assertRedirect(route('modules.show', $module));

    $module->refresh();

    expect($module->status)->toBeTrue()
        ->and($module->settings['tabs']['academic'])->toBeFalse()
        ->and($module->settings['tabs']['enrolments'])->toBeTrue();
});

test('module status can be toggled from index', function () {
    $user = moduleSettingsUser();
    $module = Module::query()->where('slug', 'dashboards')->firstOrFail();

    expect($module->status)->toBeTrue();

    $this->actingAs($user)
        ->from(route('modules.index'))
        ->put(route('modules.update-status', $module), ['status' => false])
        ->assertRedirect(route('modules.index'));

    $module->refresh();

    expect($module->status)->toBeFalse();
});

test('non-dashboard modules ignore tab settings payload', function () {
    $user = moduleSettingsUser();
    $module = Module::query()->create([
        'title' => 'Reports Module',
        'description' => 'Reports',
        'status' => true,
    ]);

    $this->actingAs($user)
        ->put(route('modules.settings', $module), [
            'status' => false,
            'settings' => [
                'tabs' => [
                    'overview' => false,
                    'academic' => false,
                    'enrolments' => false,
                    'attendance' => false,
                    'staff' => false,
                    'finance' => false,
                    'hostel' => false,
                ],
            ],
        ])
        ->assertRedirect(route('modules.show', $module));

    $module->refresh();

    expect($module->status)->toBeFalse()
        ->and($module->settings)->toBeNull();
});
