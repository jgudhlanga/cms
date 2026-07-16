<?php

use App\Models\Examinations\ExaminationResult;
use App\Models\Users\User;
use App\Policies\Examinations\ExaminationPolicy;
use Spatie\Permission\Models\Permission;

it('allows import when user has import:examinations', function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('import:examinations', 'web');
    $user->givePermissionTo('import:examinations');

    $policy = new ExaminationPolicy;

    expect($policy->import($user))->toBeTrue();
});

it('denies viewAny without permission', function (): void {
    $user = User::factory()->create();
    $policy = new ExaminationPolicy;

    expect($policy->viewAny($user))->toBeFalse();
});

it('allows view with view:examinations', function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('view:examinations', 'web');
    $user->givePermissionTo('view:examinations');

    $policy = new ExaminationPolicy;

    expect($policy->view($user, new ExaminationResult))->toBeTrue();
});
