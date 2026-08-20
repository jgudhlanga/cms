<?php

declare(strict_types=1);

namespace App\Http\Requests\Examinations;

use App\Models\Examinations\ExaminationResult;
use Illuminate\Foundation\Http\FormRequest;

class ExaminationDashboardRequest extends FormRequest
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
            'session' => ['nullable', 'string', 'max:100'],
            'discipline' => ['nullable', 'string', 'max:255'],
            'subject_code' => ['nullable', 'string', 'max:100'],
            'compare_session' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array{
     *     session?: string|null,
     *     discipline?: string|null,
     *     subject_code?: string|null,
     *     compare_session?: string|null,
     * }
     */
    public function filters(): array
    {
        /** @var array{
         *     session?: string|null,
         *     discipline?: string|null,
         *     subject_code?: string|null,
         *     compare_session?: string|null,
         * } $validated
         */
        $validated = $this->validated();

        return $validated;
    }
}
