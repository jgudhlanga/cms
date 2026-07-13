<?php

namespace App\Http\Requests\Integrations;

use App\Enums\Integrations\PaymentCurrencyCodeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Models\Shared\FeeType;
use App\Services\HMS\AccommodationPaymentQuoteService;
use App\Services\HMS\StudentAccommodationFeeService;
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

            if ($feeTypeEnum?->isAccommodationFee()) {
                $this->validateAccommodationPaymentQuote($validator);
            }
        });
    }

    private function validateAccommodationPaymentQuote(Validator $validator): void
    {
        $currency = PaymentCurrencyCodeEnum::tryFromCode($this->input('currencyCode'));

        if ($currency === null) {
            $validator->errors()->add('currencyCode', __('students.accommodation_payment_currency_invalid'));

            return;
        }

        $student = $this->user()?->studentProfile;

        if ($student === null) {
            $validator->errors()->add('amount', __('students.accommodation_payment_quote_invalid'));

            return;
        }

        $feeService = app(StudentAccommodationFeeService::class);
        $openApplication = $feeService->openAwaitingPaymentApplication($student);

        if ($openApplication === null || (int) $this->input('ledgerableId') !== $openApplication->id) {
            $validator->errors()->add('ledgerableId', __('students.accommodation_payment_application_required'));

            return;
        }

        $quote = app(AccommodationPaymentQuoteService::class)->quoteForCurrency(
            $student,
            $currency->selectionValue(),
            app(StudentAccommodationFeeService::class)->resolveFeeStructureForStudent($student, $openApplication),
        );

        if ($quote === null) {
            $validator->errors()->add('amount', __('students.accommodation_payment_quote_invalid'));

            return;
        }

        if (bccomp((string) $this->input('amount'), $quote['paymentAmount'], 2) !== 0) {
            $validator->errors()->add('amount', __('students.accommodation_payment_quote_invalid'));
        }
    }
}
