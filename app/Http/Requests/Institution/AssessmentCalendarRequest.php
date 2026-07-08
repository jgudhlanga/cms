<?php

namespace App\Http\Requests\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssessmentCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_calendar_id' => ['required', 'integer', 'exists:academic_calendars,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'type' => ['required', Rule::enum(AcademicCalendarTypeEnum::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $assessmentType = $this->route('assessment_type');
            if (! $assessmentType instanceof AssessmentType) {
                return;
            }

            $academicCalendar = AcademicCalendar::query()->find($this->integer('academic_calendar_id'));
            if (! $academicCalendar instanceof AcademicCalendar) {
                return;
            }

            $type = AcademicCalendarTypeEnum::tryFrom((string) $this->input('type'));
            if (! $type instanceof AcademicCalendarTypeEnum) {
                return;
            }

            $academicCalendarType = $academicCalendar->type instanceof AcademicCalendarTypeEnum
                ? $academicCalendar->type
                : AcademicCalendarTypeEnum::tryFrom((string) $academicCalendar->type);

            if ($academicCalendarType !== $type) {
                $validator->errors()->add(
                    'type',
                    __('trans.assessment_calendar_type_mismatch'),
                );

                return;
            }

            $editingCalendar = $this->route('calendar');
            $editingId = $editingCalendar instanceof AssessmentCalendar ? $editingCalendar->id : null;

            $duplicateExists = AssessmentCalendar::query()
                ->where('assessment_type_id', $assessmentType->id)
                ->where('academic_calendar_id', $academicCalendar->id)
                ->when($editingId, fn ($query) => $query->where('id', '!=', $editingId))
                ->exists();

            if ($duplicateExists) {
                $validator->errors()->add(
                    'academic_calendar_id',
                    __('trans.assessment_calendar_academic_calendar_already_used'),
                );

                return;
            }

            $startDate = $this->date('start_date');
            $endDate = $this->date('end_date');
            $openingDate = Carbon::parse($academicCalendar->opening_date);
            $closingDate = Carbon::parse($academicCalendar->closing_date);

            if ($startDate->lt($openingDate) || $startDate->gt($closingDate)) {
                $validator->errors()->add('start_date', __('trans.assessment_calendar_date_out_of_range', [
                    'field' => __('trans.start_date'),
                    'opening' => $openingDate->toDateString(),
                    'closing' => $closingDate->toDateString(),
                ]));
            }

            if ($endDate->lt($openingDate) || $endDate->gt($closingDate)) {
                $validator->errors()->add('end_date', __('trans.assessment_calendar_date_out_of_range', [
                    'field' => __('trans.end_date'),
                    'opening' => $openingDate->toDateString(),
                    'closing' => $closingDate->toDateString(),
                ]));
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $existingCount = AssessmentCalendar::query()
                ->where('assessment_type_id', $assessmentType->id)
                ->where('type', $type->value)
                ->when($editingId, fn ($query) => $query->where('id', '!=', $editingId))
                ->whereHas('academicCalendar', fn ($query) => $query->where('calendar_year', $academicCalendar->calendar_year))
                ->count();

            if ($existingCount >= $type->maxAssessmentCalendarsPerYear()) {
                $validator->errors()->add(
                    'type',
                    __('trans.assessment_calendar_year_limit_reached', [
                        'type' => ucfirst($type->value),
                        'max' => $type->maxAssessmentCalendarsPerYear(),
                        'year' => $academicCalendar->calendar_year,
                    ]),
                );
            }
        });
    }
}
