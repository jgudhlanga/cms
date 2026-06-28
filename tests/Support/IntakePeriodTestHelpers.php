<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\IntakePeriod;

function ensureCurrentIntakeStatus(string $status): IntakePeriod
{
    $tenantId = TenantEnum::HARARE_POLY->id();

    IntakePeriod::query()->update([
        'end_date' => now()->subYears(2)->toDateString(),
    ]);

    return IntakePeriod::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Current Intake '.uniqid(),
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
        'status' => $status,
    ]);
}
