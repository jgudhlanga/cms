<?php

declare(strict_types=1);

namespace Database\Seeders\Institution;

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\IntakePeriod;
use Illuminate\Database\Seeder;

class ContinuousIntakePeriodSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = TenantEnum::HARARE_POLY->id();

        $existing = IntakePeriod::query()
            ->where('tenant_id', $tenantId)
            ->where('is_continuous', true)
            ->where('is_active', true)
            ->first();

        if ($existing !== null) {
            return;
        }

        IntakePeriod::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'SDP / OJET Intake',
            'calendar_year' => (string) now()->year,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'description' => 'Continuous intake for Skills Development Program (SDP) and OJET applications.',
            'is_active' => true,
            'status' => IntakePeriodStatusEnum::Open,
            'is_continuous' => true,
        ]);
    }
}
