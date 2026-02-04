<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicCalendars\AcademicCalendarOptionResource;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarOption;
use App\Models\Institution\IntakePeriod;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {

    }
}
