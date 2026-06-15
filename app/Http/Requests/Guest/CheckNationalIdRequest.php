<?php

namespace App\Http\Requests\Guest;

use App\Rules\ZimbabweanIdNumber;
use Illuminate\Foundation\Http\FormRequest;

class CheckNationalIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_number' => ['required', 'string', 'max:20', new ZimbabweanIdNumber],
        ];
    }
}
