<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgrammeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage:programme-structures') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'duration_years' => ['required', 'integer', 'min:1', 'max:10'],
            'taught_semester_count' => ['required', 'integer', 'min:1', 'max:24'],
            'includes_industrial_attachment' => ['required', 'boolean'],
            'attachment_semester_count' => ['required', 'integer', 'min:0', 'max:12'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $includesAttachment = filter_var(
            $this->input('includes_industrial_attachment'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE,
        );

        if ($includesAttachment === false) {
            $this->merge(['attachment_semester_count' => 0]);
        } elseif ($includesAttachment === true && (int) $this->input('attachment_semester_count', 0) < 1) {
            $this->merge(['attachment_semester_count' => 2]);
        }
    }
}
