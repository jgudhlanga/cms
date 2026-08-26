<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserActivityIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'string', Rule::in(['created', 'updated', 'deleted'])],
            'log_name' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array{
     *     search: string|null,
     *     event: string|null,
     *     log_name: string|null,
     *     from: string|null,
     *     to: string|null,
     *     per_page: int,
     * }
     */
    public function filters(): array
    {
        return [
            'search' => $this->filled('search') ? trim((string) $this->string('search')) : null,
            'event' => $this->filled('event') ? $this->string('event')->toString() : null,
            'log_name' => $this->filled('log_name') ? $this->string('log_name')->toString() : null,
            'from' => $this->filled('from') ? $this->date('from')?->toDateString() : null,
            'to' => $this->filled('to') ? $this->date('to')?->toDateString() : null,
            'per_page' => $this->integer('per_page', 20),
        ];
    }
}
