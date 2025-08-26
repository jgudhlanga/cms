<?php

namespace App\Http\Requests\DocumentTemplates;

use Illuminate\Foundation\Http\FormRequest;

class DocumentTemplateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:document_templates,name,' . $this->document_template_id],
            'header_logo_1' => ['nullable', 'file', 'max:5009'],
            'header_logo_2' => ['nullable', 'file', 'max:5009'],
        ];
    }
}
