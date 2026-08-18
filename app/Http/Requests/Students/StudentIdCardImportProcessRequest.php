<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\StudentIdCardRequest;
use Illuminate\Foundation\Http\FormRequest;

class StudentIdCardImportProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('import', StudentIdCardRequest::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.rowNumber' => ['required', 'integer', 'min:1'],
            'rows.*.studentId' => ['required', 'integer', 'exists:students,id'],
        ];
    }
}
