<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportPastelRequest extends FormRequest
{
    public const DEFAULT_STUDENT_NUMBER_STARTS_WITH = '26';

    public function authorize(): bool
    {
        return $this->user()?->can('exportToPastel') ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'intake_period_id' => ['required', 'integer', Rule::exists('intake_periods', 'id')],
            'workflow_step_ids' => ['nullable', 'array'],
            'workflow_step_ids.*' => ['integer', Rule::exists('workflow_steps', 'id')],
            'student_number_starts_with' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return list<int>
     */
    public function workflowStepIds(): array
    {
        $ids = $this->validated('workflow_step_ids') ?? [];

        return array_values(array_map('intval', $ids));
    }

    public function intakePeriodId(): int
    {
        return (int) $this->validated('intake_period_id');
    }

    public function studentNumberStartsWith(): ?string
    {
        if (! $this->has('student_number_starts_with')) {
            return null;
        }

        $value = trim((string) ($this->validated('student_number_starts_with') ?? ''));

        return $value !== '' ? $value : null;
    }
}
