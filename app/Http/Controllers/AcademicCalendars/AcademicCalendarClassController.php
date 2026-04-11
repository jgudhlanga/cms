<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\UpdateAcademicCalendarClassRequest;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Http\RedirectResponse;

class AcademicCalendarClassController extends Controller
{
    public function update(
        UpdateAcademicCalendarClassRequest $request,
        InstitutionDepartment $institutionDepartment,
        AcademicCalendar $academicCalendar,
        AcademicCalendarClass $academicCalendarClass
    ): RedirectResponse {
        $this->authorize('update', $academicCalendar);

        $academicCalendarClass->loadMissing('classConfig');
        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (int) $classConfig->academic_calendar_id === (int) $academicCalendar->id,
            404
        );

        $validated = $request->validated();

        $academicCalendarClass->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', __('academic_calendar.update_class_success'));
    }
}
