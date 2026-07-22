<?php

namespace App\Helpers;

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use BackedEnum;
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
                ->get([
                    'id',
                    'name',
                    'start_date',
                    'end_date',
                    'is_active',
                    'status',
                    'is_continuous',
                    'description',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ])
                ->map(function (IntakePeriod $period): array {
                    $row = $period->only([
                        'id',
                        'name',
                        'start_date',
                        'end_date',
                        'is_active',
                        'status',
                        'is_continuous',
                        'description',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ]);

                    $status = $row['status'] ?? null;
                    $row['status'] = $status instanceof BackedEnum ? $status->value : $status;
                    $row['is_continuous'] = (bool) ($row['is_continuous'] ?? false);

                    return $row;
                })
                ->values()
                ->all());
        }

        return collect($rows)->map(fn (array $row): object => (object) $row);
    }

    public static function getSemestersForCalendarYear(string $calendarYear): Collection
    {
        return AcademicCalendar::semestersForCalendarYear($calendarYear);
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

        $requiredKeys = ['id', 'name', 'status', 'is_continuous'];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                return false;
            }

            foreach ($requiredKeys as $key) {
                if (! array_key_exists($key, $row)) {
                    return false;
                }
            }
        }

        return true;
    }
}
