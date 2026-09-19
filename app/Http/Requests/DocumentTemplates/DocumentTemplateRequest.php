<?php

declare(strict_types=1);

namespace App\Http\Requests\DocumentTemplates;

use App\Enums\Shared\DocumentTypeEnum;
use App\Models\Institution\DocumentTemplate;
use App\Models\Shared\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $template = $this->route('document_template');
        $templateId = $template instanceof DocumentTemplate ? $template->id : $template;
        $tenantId = $this->user()?->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('document_templates', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($templateId),
            ],
            'document_type_id' => [
                'required',
                'integer',
                'exists:document_types,id',
                function (string $attribute, mixed $value, callable $fail): void {
                    $name = DocumentType::query()->whereKey($value)->value('name');
                    if ($name === DocumentTypeEnum::OFFER_LETTER->name()) {
                        $fail(__('trans.document_template_offer_letter_moved'));
                    }
                },
            ],
            'header_logo_1' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5009'],
            'header_logo_2' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5009'],
        ];
    }
}
