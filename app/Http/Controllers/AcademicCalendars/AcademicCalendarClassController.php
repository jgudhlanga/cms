<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Http\Controllers\Concerns\ResolvesAcademicCalendarFromCalendarYear;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\AssignAcademicCalendarClassLecturerRequest;
use App\Http\Requests\AcademicCalendars\UpdateAcademicCalendarClassRequest;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Http\RedirectResponse;

class AcademicCalendarClassController extends Controller
{
    use ResolvesAcademicCalendarFromCalendarYear;

    public function update(
        UpdateAcademicCalendarClassRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass
    ): RedirectResponse {
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $this->authorize('update', $academicCalendar);

        $academicCalendarClass->loadMissing('classConfig');
        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === (string) $academicCalendar->calendar_year,
            404
        );

        $validated = $request->validated();

        $academicCalendarClass->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', __('academic_calendar.update_class_success'));
    }

    public function assignLecturer(
        AssignAcademicCalendarClassLecturerRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass,
    ): RedirectResponse {
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $this->authorize('update', $academicCalendar);

        $academicCalendarClass->loadMissing('classConfig');
        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === (string) $academicCalendar->calendar_year,
            404
        );

        $lecturerType = ClassMetaDataType::query()
            ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
            ->firstOrFail();

        $staffId = $request->validated('staff_id');

        if ($staffId === null) {
            AcademicCalendarClassMetaData::query()
                ->where('academic_calendar_class_id', $academicCalendarClass->id)
                ->where('class_metadata_type_id', $lecturerType->id)
                ->delete();

            return back()->with('success', __('academic_calendar.lecturer_assigned_success'));
        }

        AcademicCalendarClassMetaData::query()->updateOrCreate(
            [
                'academic_calendar_class_id' => $academicCalendarClass->id,
                'class_metadata_type_id' => $lecturerType->id,
            ],
            [
                'tenant_id' => $academicCalendarClass->tenant_id,
                'staff_id' => (int) $staffId,
            ],
        );

        return back()->with('success', __('academic_calendar.lecturer_assigned_success'));
    }
}
