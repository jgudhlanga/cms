<?php

namespace App\Http\Requests\Institution;

use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Institution\InstitutionDepartment;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Keeps a department window nested inside its global window and prevents changes that would
 * silently reopen capture: nothing is set after the global window closes, the start date is fixed
 * once the window opens, and a closed department window can no longer be changed.
 */
class DepartmentAssessmentCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $existing = $this->route('department_assessment_calendar');

        if ($existing instanceof DepartmentAssessmentCalendar) {
            return $this->user()?->can('update', $existing) ?? false;
        }

        return $this->user()?->can('create', [DepartmentAssessmentCalendar::class, $this->route('department')]) ?? false;
    }

    public function rules(): array
    {
        $isUpdate = $this->route('department_assessment_calendar') instanceof DepartmentAssessmentCalendar;

        return [
            'assessment_calendar_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:assessment_calendars,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'first_notification_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
            'second_notification_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
            'due_notification_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $department = $this->route('department');
            $existing = $this->route('department_assessment_calendar');
            $globalCalendar = $existing instanceof DepartmentAssessmentCalendar
                ? $existing->assessmentCalendar
                : AssessmentCalendar::query()->find($this->integer('assessment_calendar_id'));

            if (! $department instanceof InstitutionDepartment || ! $globalCalendar instanceof AssessmentCalendar) {
                $validator->errors()->add('assessment_calendar_id', __('validation.exists', ['attribute' => 'assessment calendar']));

                return;
            }

            $today = now()->startOfDay();
            $globalStart = Carbon::parse($globalCalendar->start_date)->startOfDay();
            $globalEnd = Carbon::parse($globalCalendar->end_date)->startOfDay();
            $start = Carbon::parse((string) $this->input('start_date'))->startOfDay();
            $end = Carbon::parse((string) $this->input('end_date'))->startOfDay();

            if ($today->gt($globalEnd)) {
                $validator->errors()->add(
                    'assessment_calendar_id',
                    __('academic_calendar.department_assessment_calendar_global_closed'),
                );

                return;
            }

            if ($existing instanceof DepartmentAssessmentCalendar) {
                $existingStart = Carbon::parse($existing->start_date)->startOfDay();
                $existingEnd = Carbon::parse($existing->end_date)->startOfDay();

                if ($today->gt($existingEnd)) {
                    $validator->errors()->add('end_date', __('academic_calendar.department_assessment_calendar_closed_immutable'));

                    return;
                }

                if ($today->gte($existingStart) && ! $start->equalTo($existingStart)) {
                    $validator->errors()->add('start_date', __('academic_calendar.department_assessment_calendar_started_immutable'));
                }
            } elseif (
                DepartmentAssessmentCalendar::query()
                    ->where('assessment_calendar_id', $globalCalendar->id)
                    ->where('institution_department_id', $department->id)
                    ->exists()
            ) {
                $validator->errors()->add('assessment_calendar_id', __('academic_calendar.department_assessment_calendar_already_set'));

                return;
            }

            $outsideMessage = __('academic_calendar.department_assessment_calendar_outside_global', [
                'start' => $globalStart->toDateString(),
                'end' => $globalEnd->toDateString(),
            ]);

            if ($start->lt($globalStart) || $start->gt($globalEnd)) {
                $validator->errors()->add('start_date', $outsideMessage);
            }

            if ($end->gt($globalEnd) || $end->lt($globalStart)) {
                $validator->errors()->add('end_date', $outsideMessage);
            }

            if ($end->lt($today)) {
                $validator->errors()->add('end_date', __('academic_calendar.department_assessment_calendar_end_in_past'));
            }

            $firstDays = $this->daysBefore('first_notification_days_before', $globalCalendar, MissingMarksNotificationTierEnum::First);
            $secondDays = $this->daysBefore('second_notification_days_before', $globalCalendar, MissingMarksNotificationTierEnum::Second);
            $dueDays = $this->daysBefore('due_notification_days_before', $globalCalendar, MissingMarksNotificationTierEnum::Due);

            if ($firstDays < $secondDays) {
                $validator->errors()->add('first_notification_days_before', __('trans.assessment_calendar_notification_interval_order'));
            }

            if ($secondDays < $dueDays) {
                $validator->errors()->add('second_notification_days_before', __('trans.assessment_calendar_notification_interval_order'));
            }
        });
    }

    private function daysBefore(string $field, AssessmentCalendar $globalCalendar, MissingMarksNotificationTierEnum $tier): int
    {
        $value = $this->input($field);

        return $value === null || $value === '' ? $globalCalendar->daysBeforeFor($tier) : (int) $value;
    }
}
