<?php

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\HmsSetting;
use App\Services\HMS\HostelApplicationWindowService;

it('allows applications when the toggle is on', function (): void {
    openHostelApplications(TenantEnum::HARARE_POLY->id());

    $service = app(HostelApplicationWindowService::class);
    $result = $service->windowStatus(TenantEnum::HARARE_POLY->id());

    expect($result['success'])->toBeTrue()
        ->and($result['blocker'])->toBeNull();
});

it('returns applications closed when the toggle is off', function (): void {
    HmsSetting::resolveForTenant(TenantEnum::HARARE_POLY->id())->update([
        'applications_open' => false,
    ]);

    $service = app(HostelApplicationWindowService::class);
    $result = $service->windowStatus(TenantEnum::HARARE_POLY->id());

    expect($result['success'])->toBeFalse()
        ->and($result['blocker'])->toBe(HostelApplicationWindowService::BLOCKER_APPLICATIONS_CLOSED);
});

it('returns configured application dates from settings', function (): void {
    openHostelApplications(
        TenantEnum::HARARE_POLY->id(),
        '2026-01-01',
        '2026-06-30',
    );

    $service = app(HostelApplicationWindowService::class);
    $dates = $service->configuredApplicationDates(TenantEnum::HARARE_POLY->id());

    expect($dates['checkIn'])->toBe('2026-01-01')
        ->and($dates['checkOut'])->toBe('2026-06-30');
});
