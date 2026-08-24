<?php

namespace App\Http\Requests\Institution;

use App\Support\Institution\DepartmentColorPalette;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property mixed $department_ids
 */
class InstitutionDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->department_ids)) {
            $this->merge([
                'department_ids' => json_decode($this->department_ids, true),
            ]);
        }

        if ($this->filled('color_code')) {
            $this->merge([
                'color_code' => DepartmentColorPalette::normalize($this->input('color_code')),
            ]);
        }
    }

    public function rules(): array
    {
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
                'department_code' => ['nullable', 'string', 'max:50'],
                'color_code' => [
                    'required',
                    'string',
                    'regex:/^#[0-9A-F]{6}$/',
                    Rule::unique('institution_departments', 'color_code')
                        ->where(fn ($query) => $query->where('tenant_id', auth()->user()?->tenant_id))
                        ->ignore($this->route('department')?->id),
                ],
                'description' => ['nullable', 'string', 'max:1000'],
            ];
        }

        return [
            'is_academic' => ['required', 'boolean'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'color_code.unique' => __('trans.department_color_must_be_unique'),
        ];
    }
}
