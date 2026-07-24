<?php

namespace App\Http\Requests\Rbac;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $permission
 */
class PermissionRequest extends FormRequest
{

	public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
			'name' => ['required', 'string', 'max:255', 'unique:permissions,name,'.$this->permission?->id],
        ];
    }
}
