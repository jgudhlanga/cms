<?php

namespace App\Http\Controllers\Api\V1\AcademicCalendars;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use Illuminate\Http\Request;

class AcademicCalendarController extends Controller
{
    public function index()
    {
        return AcademicCalendarResource::collection(AcademicCalendar::paginate(100));
    }

    public function getOptions()
    {

    }
}
