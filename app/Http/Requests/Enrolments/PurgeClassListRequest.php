<?php

namespace App\Http\Requests\Enrolments;

use Illuminate\Foundation\Http\FormRequest;

class PurgeClassListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete:class-lists') ?? false;
    }

    public function rules(): array
    {
        return [
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['required', 'integer', 'exists:student_applications,id'],
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'A note explaining the removal is required.',
            'note.min' => 'The note must be at least 10 characters.',
        ];
    }
}
