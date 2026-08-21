<?php

declare(strict_types=1);

namespace App\Http\Requests\Examinations;

use App\Models\Examinations\ExaminationResult;
use Illuminate\Foundation\Http\FormRequest;

class ExaminationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ExaminationResult::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'session' => ['nullable', 'array'],
            'session.*' => ['string', 'max:100'],
            'discipline' => ['nullable', 'string', 'max:255'],
            'subject_code' => ['nullable', 'string', 'max:100'],
            'surname' => ['nullable', 'string', 'max:255'],
            'first_names' => ['nullable', 'string', 'max:255'],
            'candidate_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $session = $this->input('session');

        if ($session === null || $session === '') {
            $this->merge(['session' => []]);

            return;
        }

        if (is_string($session)) {
            $this->merge(['session' => [trim($session)]]);
        }
    }

    /**
     * @return array{
     *     session?: list<string>,
     *     discipline?: string|null,
     *     subject_code?: string|null,
     *     surname?: string|null,
     *     first_names?: string|null,
     *     candidate_number?: string|null,
     * }
     */
    public function filters(): array
    {
        /** @var array{
         *     session?: list<string>,
         *     discipline?: string|null,
         *     subject_code?: string|null,
         *     surname?: string|null,
         *     first_names?: string|null,
         *     candidate_number?: string|null,
         * } $validated
         */
        $validated = $this->validated();

        return $validated;
    }
}
