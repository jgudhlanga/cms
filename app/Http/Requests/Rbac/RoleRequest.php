<?php

namespace App\Http\Requests\Rbac;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $role
 */
class RoleRequest extends FormRequest
{

	public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
			'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$this->role?->id],
        ];
    }
}
