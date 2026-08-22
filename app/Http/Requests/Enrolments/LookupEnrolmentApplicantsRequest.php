<?php

declare(strict_types=1);

namespace App\Http\Requests\Enrolments;

use App\Helpers\EnrolmentHelper;
use App\Models\Users\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LookupEnrolmentApplicantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        $type = $this->string('type')->toString();

        if ($type !== '') {
            $permission = EnrolmentHelper::classListBrowsePermissionForType($type);

            return $permission !== null && $user->can($permission);
        }

        return EnrolmentHelper::classListBrowseTypesForUser($user) !== [];
    }

    /**
     * @return list<string>
     */
    public function browseTypes(): array
    {
        $type = $this->validated('type');

        if (is_string($type) && $type !== '') {
            return [$type];
        }

        $user = $this->user();

        return $user instanceof User
            ? EnrolmentHelper::classListBrowseTypesForUser($user)
            : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(EnrolmentHelper::classListBrowseTypes())],
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
        $type = $this->input('type');

        $this->merge([
            'q' => is_string($query) && trim($query) !== '' ? $query : null,
            'type' => is_string($type) && trim($type) !== '' ? $type : null,
        ]);
    }
}
