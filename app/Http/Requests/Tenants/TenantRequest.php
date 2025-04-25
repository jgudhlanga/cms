<?php

namespace App\Http\Requests\Tenants;


use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $tenant
 */
class TenantRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:tenants,name,'.$this->tenant->id]
        ];
    }
}
