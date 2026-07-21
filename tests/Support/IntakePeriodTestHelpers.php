<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\IntakePeriod;

function ensureCurrentIntakeStatus(string $status, bool $isContinuous = false): IntakePeriod
{
    $tenantId = TenantEnum::HARARE_POLY->id();

    if ($isContinuous) {
        IntakePeriod::query()
            ->where('is_continuous', true)
            ->update([
                'is_active' => false,
                'status' => IntakePeriodStatusEnum::Closed,
            ]);
    } else {
        IntakePeriod::query()
            ->where('is_continuous', false)
            ->update([
                'end_date' => now()->subYears(2)->toDateString(),
                'status' => IntakePeriodStatusEnum::Closed,
            ]);
    }

    return IntakePeriod::query()->create([
        'tenant_id' => $tenantId,
        'name' => ($isContinuous ? 'Continuous Intake ' : 'Current Intake ').uniqid(),
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
        'status' => $status,
        'is_continuous' => $isContinuous,
    ]);
}

function ensureContinuousIntakeOpen(): IntakePeriod
{
    return ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value, true);
}
