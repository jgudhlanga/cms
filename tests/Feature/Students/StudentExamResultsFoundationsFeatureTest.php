<?php

declare(strict_types=1);

use App\Models\Institution\InstitutionFeature;
use App\Models\Tenants\Tenant;
use App\Services\Institution\InstitutionFeatureService;

test('institution feature service defaults and toggles allow online clearance', function () {
    $tenant = Tenant::query()->first() ?? Tenant::factory()->create();
    $service = app(InstitutionFeatureService::class);

    expect($service->allowsOnlineClearance((int) $tenant->id))->toBeFalse();

    $service->setAllowOnlineClearance((int) $tenant->id, true);

    expect($service->allowsOnlineClearance((int) $tenant->id))->toBeTrue();

    $stored = InstitutionFeature::query()
        ->withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->first();

    expect($stored)->not->toBeNull()
        ->and($stored->allowsOnlineClearance())->toBeTrue();
});
