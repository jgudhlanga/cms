<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MoveAcademicCalendarClassStudentsRequest extends FormRequest
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
            'target_academic_calendar_class_id' => ['required', 'integer', 'exists:academic_calandar_classes,id'],
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
            $sourceClass = $this->route('academic_calendar_class');

            if (! $institutionDepartment instanceof InstitutionDepartment
                || ! is_string($calendarYear) || $calendarYear === ''
                || ! $sourceClass instanceof AcademicCalendarClass) {
                return;
            }

            $sourceClass->loadMissing('classConfig');
            $sourceConfig = $sourceClass->classConfig;

            if (! $sourceConfig instanceof ClassConfig) {
                $validator->errors()->add('target_academic_calendar_class_id', __('academic_calendar.move_students_invalid_source_class'));

                return;
            }

            if ((int) $sourceConfig->institution_department_id !== (int) $institutionDepartment->id
                || (string) $sourceConfig->calendar_year !== $calendarYear) {
                $validator->errors()->add('target_academic_calendar_class_id', __('academic_calendar.move_students_invalid_source_class'));

                return;
            }

            $targetId = (int) $this->input('target_academic_calendar_class_id');
            $targetClass = AcademicCalendarClass::query()->find($targetId);

            if (! $targetClass instanceof AcademicCalendarClass) {
                return;
            }

            if ($targetClass->id === $sourceClass->id) {
                $validator->errors()->add('target_academic_calendar_class_id', __('academic_calendar.move_students_same_class'));

                return;
            }

            if ((int) $targetClass->class_config_id !== (int) $sourceClass->class_config_id) {
                $validator->errors()->add('target_academic_calendar_class_id', __('academic_calendar.move_students_target_wrong_config'));

                return;
            }

            /** @var array<int, int> $studentEnrolmentIds */
            $studentEnrolmentIds = array_map('intval', $this->input('student_enrolment_ids', []));

            $countOnSource = AcademicCalendarStudentEnrolment::query()
                ->where('academic_calendar_class_id', $sourceClass->id)
                ->whereIn('student_enrolment_id', $studentEnrolmentIds)
                ->whereNull('deleted_at')
                ->count();

            if ($countOnSource !== count($studentEnrolmentIds)) {
                $validator->errors()->add('student_enrolment_ids', __('academic_calendar.move_students_not_all_on_source_class'));

                return;
            }

            $hasConflict = AcademicCalendarStudentEnrolment::query()
                ->where('academic_calendar_class_id', $targetId)
                ->whereIn('student_enrolment_id', $studentEnrolmentIds)
                ->whereNull('deleted_at')
                ->exists();

            if ($hasConflict) {
                $validator->errors()->add('student_enrolment_ids', __('academic_calendar.move_students_already_in_target'));
            }
        });
    }
}
