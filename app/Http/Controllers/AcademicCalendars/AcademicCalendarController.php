<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarOptionResource;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarOption;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
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

    public function classConfig(InstitutionDepartment $institutionDepartment, AcademicCalendar $academicCalendar)
    {
        $departmentLevelId = request()->query('department_level');
        $departmentCourseId = request()->query('department_course');
        $modeOfStudyId = request()->query('mode_of_study');
        $course = DepartmentCourse::find($departmentCourseId);
        $level = DepartmentLevel::find($departmentLevelId);
        $mode = ModeOfStudy::find($modeOfStudyId);

        return Inertia::render('institution/academicCalendars/AcademicCalendarClassesConfig', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'course' => DepartmentCourseResource::make($course),
            'level' => DepartmentLevelResource::make($level),
            'mode' => ModeOfStudyResource::make($mode),
        ]);
    }

    public function storeClassConfig(AcademicCalendar $academicCalendar)
    {

    }
}
