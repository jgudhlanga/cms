<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ReconcileFinanceTransactionQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_statement_id' => ['nullable', 'integer', 'exists:zb_bank_statements,id'],
        ];
    }
}
