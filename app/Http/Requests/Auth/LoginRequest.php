<?php

namespace App\Http\Requests\Auth;

use App\Enums\Shared\StatusEnum;
use App\Http\Requests\Auth\Concerns\ThrottlesLogins;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    use ThrottlesLogins;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $accountInactive = false;

        $authenticated = Auth::attemptWhen(
            $this->only('email', 'password'),
            function ($user) use (&$accountInactive): bool {
                $accountInactive = (int) $user->status_id === StatusEnum::INACTIVE->id();

                return ! $accountInactive;
            },
            $this->boolean('remember'),
        );

        if ($accountInactive) {
            throw ValidationException::withMessages([
                'email' => trans('auth.inactive'),
            ]);
        }

        if (! $authenticated) {
            $this->hitLoginRateLimit();

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $this->clearLoginRateLimit();
    }
}
