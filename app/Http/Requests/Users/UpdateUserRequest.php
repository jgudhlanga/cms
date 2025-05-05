<?php

namespace App\Http\Requests\Users;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * @property mixed $user
 */
class UpdateUserRequest extends FormRequest
{

	public function authorize(): bool
	{
		return true;
	}


	public function rules(): array
	{
		return [
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->user->id],
			'password' => [
				'nullable',
				Password::min(8)
					->letters()
					->numbers()
					->symbols()
					->mixedCase()
			],
		];
	}
}
