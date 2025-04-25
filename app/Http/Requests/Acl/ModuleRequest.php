<?php

namespace App\Http\Requests\Acl;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $module
 */
class ModuleRequest extends FormRequest
{

	public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
			'title' => ['required', 'string', 'max:255', 'unique:modules,title,'.$this->module?->id],
        ];
    }
}
