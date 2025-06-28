<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

class NextOfKinRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'next_of_kin_name' => ['required', 'string', 'max:255'],
            ['relationship_id', 'exists:relationships,id'],
        ];
    }
}
