<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Support\RecipientEmailParser;
use Illuminate\Foundation\Http\FormRequest;

class ExportStudentEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'recipient_emails' => RecipientEmailParser::parse($this->input('recipient_emails')),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'intake_year' => ['nullable', 'string', 'max:20'],
            'recipient_emails' => ['required', 'array', 'min:1'],
            'recipient_emails.*' => ['required', 'email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient_emails.required' => __('trans.maintenance_export_recipient_emails_required'),
            'recipient_emails.min' => __('trans.maintenance_export_recipient_emails_required'),
            'recipient_emails.*.email' => __('trans.maintenance_export_recipient_emails_invalid'),
        ];
    }
}
