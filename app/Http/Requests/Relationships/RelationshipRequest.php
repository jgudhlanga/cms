<?php

namespace App\Http\Requests\Relationships;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $relationship
 */
class RelationshipRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:relationships,name,' . $this->relationship?->id],
        ];
    }
}
