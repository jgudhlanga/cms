<?php

namespace App\Http\Requests\Ledgers;

use Illuminate\Foundation\Http\FormRequest;

class LedgerRequest extends FormRequest
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
