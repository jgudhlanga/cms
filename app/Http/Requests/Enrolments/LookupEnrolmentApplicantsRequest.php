<?php

declare(strict_types=1);

namespace App\Http\Requests\Enrolments;

use App\Helpers\EnrolmentHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LookupEnrolmentApplicantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = EnrolmentHelper::classListBrowsePermissionForType(
            $this->string('type')->toString() ?: null,
        );

        return $permission !== null && ($this->user()?->can($permission) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(EnrolmentHelper::classListBrowseTypes())],
            'intake_period_id' => ['required', 'integer'],
            'institution_department_id' => ['nullable', 'integer'],
            'department_level_id' => ['nullable', 'integer'],
            'department_course_id' => ['nullable', 'integer', 'required_without:q'],
            'q' => ['nullable', 'string', 'min:2', 'max:100', 'required_without:department_course_id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $query = $this->input('q');

        if (! is_string($query) || trim($query) === '') {
            $this->merge(['q' => null]);
        }
    }
}
