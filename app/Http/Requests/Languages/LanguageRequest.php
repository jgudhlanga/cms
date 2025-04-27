<?php

namespace App\Http\Requests\Languages;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $language
 */
class LanguageRequest extends FormRequest
{

	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'title' => ['required', 'string', 'max:255', 'unique:languages,title,' . $this->language?->id]
		];
	}
}
