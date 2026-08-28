<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;

class MaintenanceExportOptionsService
{
    /**
     * @return list<string>
     */
    public function calendarYears(): array
    {
        return AcademicCalendar::query()
            ->select('calendar_year')
            ->distinct()
            ->orderByDesc('calendar_year')
            ->pluck('calendar_year')
            ->filter(static fn (?string $year): bool => $year !== null && $year !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function semesters(): array
    {
        return Semester::query()
            ->orderBy('slug')
            ->get(['id', 'name', 'slug'])
            ->map(static fn (Semester $semester): array => [
                'id' => (int) $semester->id,
                'name' => (string) $semester->name,
                'slug' => (string) $semester->slug,
            ])
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function calendarTypes(): array
    {
        return array_map(
            static fn (AcademicCalendarTypeEnum $type): array => [
                'value' => $type->value,
                'label' => strtoupper($type->value) === 'ABMA' ? 'ABMA' : ucfirst($type->value),
            ],
            AcademicCalendarTypeEnum::cases(),
        );
    }
}
