<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class LookupPortalExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewOwnExamResults') ?? false;
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
}
