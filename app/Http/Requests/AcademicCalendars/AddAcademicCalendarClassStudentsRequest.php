<?php

declare(strict_types=1);

namespace App\Http\Requests\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\InstitutionDepartment;
use App\Queries\AcademicCalendars\UnassignedClassConfigStudentsQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddAcademicCalendarClassStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_enrolment_ids' => ['required', 'array', 'min:1'],
            'student_enrolment_ids.*' => ['integer', 'distinct', 'exists:student_enrolments,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $institutionDepartment = $this->route('institution_department');
            $calendarYear = $this->route('calendar_year');
            $academicCalendarClass = $this->route('academic_calendar_class');

            if (! $institutionDepartment instanceof InstitutionDepartment
                || ! is_string($calendarYear) || $calendarYear === ''
                || ! $academicCalendarClass instanceof AcademicCalendarClass) {
                return;
            }

            $academicCalendarClass->loadMissing('classConfig');
            $classConfig = $academicCalendarClass->classConfig;

            if (! $classConfig instanceof ClassConfig
                || (int) $classConfig->institution_department_id !== (int) $institutionDepartment->id
                || (string) $classConfig->calendar_year !== $calendarYear) {
                $validator->errors()->add('student_enrolment_ids', __('academic_calendar.add_students_invalid_class'));

                return;
            }

            /** @var array<int, int> $studentEnrolmentIds */
            $studentEnrolmentIds = array_map('intval', $this->input('student_enrolment_ids', []));

            $eligible = app(UnassignedClassConfigStudentsQuery::class)->eligibleRowsForEnrolmentIds(
                $institutionDepartment,
                $classConfig,
                $calendarYear,
                $studentEnrolmentIds,
            );

            if ($eligible->count() !== count($studentEnrolmentIds)) {
                $validator->errors()->add('student_enrolment_ids', __('academic_calendar.add_students_not_unassigned'));
            }
        });
    }
}
