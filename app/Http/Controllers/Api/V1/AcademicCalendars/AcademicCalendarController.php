<?php

namespace App\Http\Controllers\Api\V1\AcademicCalendars;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Models\AcademicCalendars\AcademicCalendar;

class AcademicCalendarController extends Controller
{
    public function index()
    {
        $calendars = AcademicCalendar::query()
            ->whereDate('opening_date', '<', today())
            ->orderByDesc('calendar_year')
            ->paginate();

        return AcademicCalendarResource::collection($calendars);
    }

    public function getAcademicYears()
    {
        $years = AcademicCalendar::query()
            ->whereDate('opening_date', '<', today())
            ->select('calendar_year')
            ->distinct()
            ->orderByDesc('calendar_year')
            ->pluck('calendar_year')
            ->filter(fn (?string $y): bool => $y !== null && $y !== '')
            ->values()
            ->map(fn (string $y): array => ['academicYear' => $y])
            ->all();

        return response()->json(['data' => $years]);
    }
}
