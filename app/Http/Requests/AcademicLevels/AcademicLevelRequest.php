<?php

namespace App\Http\Requests\AcademicLevels;

use Illuminate\Foundation\Http\FormRequest;

class AcademicLevelRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:academic_levels,name,' . $this->academic_level?->id],
        ];
    }
}
