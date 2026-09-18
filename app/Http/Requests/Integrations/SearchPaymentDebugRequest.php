<?php

declare(strict_types=1);

namespace App\Http\Requests\Integrations;

use Illuminate\Foundation\Http\FormRequest;

class SearchPaymentDebugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewPaymentsDebug') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => trim((string) $this->input('q', '')),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'feeType' => ['nullable', 'string', 'exists:fee_types,slug'],
        ];
    }
}
