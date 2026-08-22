<?php

declare(strict_types=1);

namespace App\Http\Requests\Enrolments;

use App\Helpers\EnrolmentHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrowseDepartmentApplicationsRequest extends FormRequest
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
        $origins = ['dashboard', 'enrolments'];

        return [
            'type' => ['required', 'string', Rule::in(EnrolmentHelper::classListBrowseTypes())],
            'intake_period_id' => ['required', 'integer'],
            'mode_of_study_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'string', Rule::in($origins)],
        ];
    }
}
