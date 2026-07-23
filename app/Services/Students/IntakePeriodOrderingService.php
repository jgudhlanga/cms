<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Institution\IntakePeriod;
use Illuminate\Support\Collection;

class IntakePeriodOrderingService
{
    /**
     * Regular intakes by end_date desc, with the active continuous intake inserted at index 1.
     *
     * @return Collection<int, IntakePeriod>
     */
    public function orderedForAdminDropdown(bool $activeOnly = true): Collection
    {
        $regularQuery = IntakePeriod::query()->regular();

        if ($activeOnly) {
            $regularQuery->where('is_active', true);
        }

        $regular = $regularQuery
            ->orderByDesc('end_date')
            ->orderBy('name')
            ->get();

        $continuousQuery = IntakePeriod::query()->continuous();

        if ($activeOnly) {
            $continuousQuery->where('is_active', true);
        }

        $continuous = $continuousQuery
            ->orderByDesc('end_date')
            ->first();

        if ($continuous === null) {
            return $regular->values();
        }

        if ($regular->isEmpty()) {
            return collect([$continuous]);
        }

        $ordered = $regular->values();
        $ordered->splice(1, 0, [$continuous]);

        return $ordered->values();
    }

    /**
     * Most recent non-continuous intake by end_date (any status), preferred for admin defaults.
     */
    public function defaultAdminIntakePeriod(): ?IntakePeriod
    {
        return IntakePeriod::query()
            ->regular()
            ->orderByDesc('end_date')
            ->first();
    }

    public function displayName(IntakePeriod $period): string
    {
        if ($period->is_continuous) {
            return __('trans.intake_period_continuous_display_name');
        }

        return (string) $period->name;
    }
}
