<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

class BankDetailRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
			'bank_id' => ['required'],
			'bank_branch_id' => ['required'],
			'bank_account_type_id' => ['required'],
			'bank_account_holder' => ['required'],
			'bank_account_number' => ['required'],
        ];
    }
}
