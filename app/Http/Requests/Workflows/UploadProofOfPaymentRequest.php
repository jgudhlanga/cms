<?php

namespace App\Http\Requests\Workflows;

use App\Enums\Shared\POPTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'proof_of_payment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,heic,heif', 'max:5009'],
        ];
    }
}
