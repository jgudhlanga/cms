<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffImportCreateLookupRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in([
                'title',
                'gender',
                'marital_status',
                'employment_type',
                'department',
                'role',
            ])],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
