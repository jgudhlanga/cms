<?php

declare(strict_types=1);

namespace App\Http\Requests\Documents;

use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OfferLetterTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode_of_study_id' => $this->blankToNull('mode_of_study_id'),
            'course_id' => $this->blankToNull('course_id'),
            'tuition_override' => $this->blankToNull('tuition_override'),
            'institution_department_ids' => array_values(array_filter(
                $this->input('institution_department_ids', []) ?: [],
                fn (mixed $id): bool => $id !== null && $id !== '' && (int) $id > 0,
            )),
            'level_ids' => array_values(array_filter(
                $this->input('level_ids', []) ?: [],
                fn (mixed $id): bool => $id !== null && $id !== '' && (int) $id > 0,
            )),
        ]);
    }

    public function rules(): array
    {
        $intake = $this->route('intake_period');
        $intakeId = $intake instanceof IntakePeriod ? $intake->id : $intake;
        $template = $this->route('offer_letter_template');
        $templateId = $template instanceof OfferLetterTemplate ? $template->id : $template;
        $tenantId = $this->user()?->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('offer_letter_templates', 'name')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('intake_period_id', $intakeId))
                    ->ignore($templateId),
            ],
            'helper_description' => ['nullable', 'string'],
            'institution_department_ids' => ['nullable', 'array'],
            'institution_department_ids.*' => ['integer', 'exists:institution_departments,id'],
            'level_ids' => ['nullable', 'array'],
            'level_ids.*' => ['integer', 'exists:levels,id'],
            'mode_of_study_id' => ['nullable', 'integer', 'exists:mode_of_studies,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'tuition_override' => ['nullable', 'numeric', 'min:0'],
            'header_logo_1' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5009'],
            'header_logo_2' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5009'],
            'body' => ['nullable', 'string'],
        ];
    }

    private function blankToNull(string $key): mixed
    {
        $value = $this->input($key);

        return $value === '' || $value === '0' ? null : $value;
    }
}
