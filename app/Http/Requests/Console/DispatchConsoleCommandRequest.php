<?php

declare(strict_types=1);

namespace App\Http\Requests\Console;

use App\Support\Console\ConsoleCommandRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DispatchConsoleCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->can('runConsoleCommands')) {
            return false;
        }

        $key = $this->string('command')->toString();

        if ($this->registry()->isDestructive($key)) {
            return $user->can('runDestructiveConsoleCommands');
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $registry = $this->registry();
        $key = $this->string('command')->toString();

        return [
            'command' => ['required', 'string', Rule::in($registry->keys())],
            'password' => ['required', 'string'],
            'parameters' => ['sometimes', 'array'],
            ...$registry->rules($key),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function parameterInput(): array
    {
        return (array) ($this->validated()['parameters'] ?? []);
    }

    private function registry(): ConsoleCommandRegistry
    {
        return app(ConsoleCommandRegistry::class);
    }
}
