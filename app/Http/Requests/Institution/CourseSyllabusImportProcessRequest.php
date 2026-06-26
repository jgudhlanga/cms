<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class CourseSyllabusImportProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preview_token' => ['required', 'string'],
            'file' => ['prohibited'],
            'row_corrections' => ['sometimes', 'array'],
            'row_corrections.*' => ['array'],
            'row_corrections.*.level' => ['sometimes', 'string', 'max:255'],
            'row_corrections.*.courseTitle' => ['sometimes', 'string', 'max:255'],
            'row_corrections.*.courseCode' => ['sometimes', 'string', 'max:255'],
            'row_corrections.*.semester' => ['sometimes', 'string', 'max:255'],
            'row_corrections.*.moduleTitle' => ['sometimes', 'string', 'max:255'],
            'row_corrections.*.moduleCode' => ['sometimes', 'string', 'max:255'],
            'row_corrections.*.allSemesters' => ['sometimes', 'boolean'],
            'excluded_row_numbers' => ['sometimes', 'array'],
            'excluded_row_numbers.*' => ['integer', 'min:1'],
        ];
    }
}
