<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

class MaritalStatusRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:marital_statuses,title,' . $this->marital_status?->id]
        ];
    }
}
