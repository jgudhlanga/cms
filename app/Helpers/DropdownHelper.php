<?php

namespace App\Helpers;

use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use Illuminate\Support\Collection;

class DropdownHelper
{
    public static function getIntakePeriods(): Collection
    {
        $rows = cache()->get('all_intake_periods');

        if (! self::isValidRows($rows)) {
            cache()->forget('all_intake_periods');
            $rows = cache()->rememberForever('all_intake_periods', fn () => IntakePeriod::query()
                ->where('is_active', 1)
                ->orderByDesc('end_date')
                ->get(['id', 'name', 'start_date', 'end_date', 'is_active', 'description', 'created_at', 'updated_at', 'deleted_at'])
                ->map(fn (IntakePeriod $period): array => $period->only([
                    'id',
                    'name',
                    'start_date',
                    'end_date',
                    'is_active',
                    'description',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]))
                ->values()
                ->all());
        }

        return collect($rows)->map(fn (array $row): object => (object) $row);
    }

    public static function getModesOfStudy(): Collection
    {
        $rows = cache()->get('all_modes_of_study');

        if (! self::isValidRows($rows)) {
            cache()->forget('all_modes_of_study');
            $rows = cache()->rememberForever('all_modes_of_study', fn () => ModeOfStudy::query()
                ->get(['id', 'name', 'description', 'created_at', 'updated_at', 'deleted_at'])
                ->map(fn (ModeOfStudy $modeOfStudy): array => $modeOfStudy->only([
                    'id',
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]))
                ->values()
                ->all());
        }

        return collect($rows)->map(fn (array $row): object => (object) $row);
    }

    private static function isValidRows(mixed $rows): bool
    {
        if (! is_array($rows)) {
            return false;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                return false;
            }
        }

        return true;
    }
}
