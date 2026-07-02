<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicCalendarClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('description') && $this->input('description') === '') {
            $this->merge(['description' => null]);
        }
    }

    public function rules(): array
    {
        /** @var AcademicCalendarClass|null $academicCalendarClass */
        $academicCalendarClass = $this->route('academic_calendar_class');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('academic_calendar_classes', 'name')
                    ->where('class_config_id', $academicCalendarClass?->class_config_id)
                    ->ignore($academicCalendarClass?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
