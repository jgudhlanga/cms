<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $id
 * @property mixed $race
 */
class RaceRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:races,title,' . $this->race?->id]
        ];
    }
}
