<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarOptionResource;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarOption;
use App\Models\Institution\IntakePeriod;
use Carbon\Carbon;
use Inertia\Inertia;

class AcademicCalendarController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AcademicCalendar::class);
        $options = AcademicCalendarOption::all();
        $calendars = AcademicCalendar::all();
        $intakePeriods = IntakePeriod::orderBy('end_date', 'desc')->get();
        return Inertia::render('academicCalendars/Index', [
            'academicCalendarOptions' => AcademicCalendarOptionResource::collection($options),
            'academicCalendars' => AcademicCalendarResource::collection($calendars),
            'intakePeriods' => IntakePeriodResource::collection($intakePeriods),
        ]);
    }

    public function store(AcademicCalendarRequest $request)
    {
        AcademicCalendar::create($this->prepareData($request));
        return back()->with('success', 'Academic Calendar created.');
    }

    public function update(AcademicCalendar $academicCalendar, AcademicCalendarRequest $request)
    {
        $academicCalendar->update($this->prepareData($request));
        return back()->with('success', 'Academic Calendar updated.');
    }


    private function prepareData(AcademicCalendarRequest $request): array
    {
        $data = $request->validated();
        $data['opening_date'] = Carbon::parse($data['opening_date'])->format('Y-m-d');
        $data['closing_date'] = Carbon::parse($data['closing_date'])->format('Y-m-d');
        $data['intake_period_ids'] = array_values($data['intake_period_ids'] ?? []);
        return $data;
    }
}
