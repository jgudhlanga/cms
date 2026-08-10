<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class LookupStudentExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewStudentExamResults:students') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'candidate_number' => ['required', 'string', 'max:64'],
        ];
    }

    protected function failedAuthorization(): void
    {
        abort(403, __('examinations.exam_results_permission_denied'));
    }
}
