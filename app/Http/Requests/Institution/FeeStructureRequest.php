<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class FeeStructureRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'fee_type_id' => ['required', 'integer:', 'exists:fee_types,id'],
            'level_id' => ['nullable', 'integer:', 'exists:levels,id'],
            'mode_of_study_id' => ['nullable', 'integer:', 'exists:mode_of_studies,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'local_fca_amount' => ['nullable', 'numeric', 'min:0']
        ];
    }
}
