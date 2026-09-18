<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportForBillingRequest extends FormRequest
{
    public const DEFAULT_STUDENT_NUMBER_STARTS_WITH = '26';

    public function authorize(): bool
    {
        return $this->user()?->can('exportForBilling') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'student_number_starts_with',
            'confirmed_from',
            'confirmed_to',
            'institution_department_id',
            'department_level_id',
            'department_course_id',
            'mode_of_study_id',
            'pastel_linked',
        ];

        $payload = [];

        foreach ($nullable as $key) {
            if ($this->exists($key) && $this->input($key) === '') {
                $payload[$key] = null;
            }
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'academic_calendar_ids' => ['required', 'array', 'min:1'],
            'academic_calendar_ids.*' => ['integer', Rule::exists('academic_calendars', 'id')],
            'student_number_starts_with' => ['nullable', 'string', 'max:50'],
            'programme_semester_ids' => ['nullable', 'array'],
            'programme_semester_ids.*' => ['integer', Rule::exists('programme_semesters', 'id')],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['string', Rule::enum(StudyPositionSourceEnum::class)],
            'sync_statuses' => ['nullable', 'array'],
            'sync_statuses.*' => ['string', Rule::enum(StudyPositionSyncStatusEnum::class)],
            'confirmed_from' => ['nullable', 'date'],
            'confirmed_to' => ['nullable', 'date', 'after_or_equal:confirmed_from'],
            'institution_department_id' => ['nullable', 'integer', Rule::exists('institution_departments', 'id')],
            'department_level_id' => ['nullable', 'integer', Rule::exists('department_levels', 'id')],
            'department_course_id' => ['nullable', 'integer', Rule::exists('department_courses', 'id')],
            'mode_of_study_id' => ['nullable', 'integer', Rule::exists('mode_of_studies', 'id')],
            'pastel_linked' => ['nullable', 'string', Rule::in(['all', 'linked', 'unlinked'])],
        ];
    }

    /**
     * @return list<int>
     */
    public function academicCalendarIds(): array
    {
        return array_values(array_map('intval', $this->validated('academic_calendar_ids')));
    }

    /**
     * @return list<int>
     */
    public function programmeSemesterIds(): array
    {
        return array_values(array_map('intval', $this->validated('programme_semester_ids') ?? []));
    }

    /**
     * @return list<string>
     */
    public function sources(): array
    {
        return array_values(array_map('strval', $this->validated('sources') ?? []));
    }

    /**
     * @return list<string>
     */
    public function syncStatuses(): array
    {
        return array_values(array_map('strval', $this->validated('sync_statuses') ?? []));
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
