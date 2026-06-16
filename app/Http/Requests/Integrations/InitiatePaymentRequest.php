<?php

namespace App\Http\Requests\Integrations;

use App\Enums\Shared\FeeTypeEnum;
use App\Models\Shared\FeeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'orderReference' => ['required', 'string', 'max:255'],
            'feeTypeId' => ['required', 'integer', 'exists:fee_types,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'itemName' => ['required', 'string', 'max:255'],
            'itemDescription' => ['nullable', 'string', 'max:255'],
            'currencyCode' => ['required', 'string', 'max:10'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobilePhoneNumber' => ['nullable', 'string', 'max:50'],
            'paymentMethod' => ['nullable', 'string', 'max:50'],
            'ledgerableId' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $feeType = FeeType::query()->find($this->input('feeTypeId'));
            if ($feeType === null) {
                return;
            }

            $feeTypeEnum = FeeTypeEnum::fromFeeType($feeType);
            if ($feeTypeEnum?->requiresLedgerableId() && blank($this->input('ledgerableId'))) {
                $validator->errors()->add('ledgerableId', __('trans.ledgerable_required'));
            }
        });
    }
}
