<?php

namespace App\Http\Requests\Religions;

use Illuminate\Foundation\Http\FormRequest;

class ReligionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            //
        ];
    }
}
