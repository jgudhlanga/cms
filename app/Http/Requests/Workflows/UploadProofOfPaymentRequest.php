<?php

namespace App\Http\Requests\Workflows;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Shared\POPTypeEnum;


class UploadProofOfPaymentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(POPTypeEnum::class)],
            'proof_of_payment' => ['required', 'file', 'max:5009'],
        ];
    }

}
