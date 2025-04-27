<?php

namespace App\Http\Requests\Titles;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $title
 */
class TitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:titles,name,'.$this->title?->id],
        ];
    }
}
