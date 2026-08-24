<?php

namespace App\Http\Requests\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use App\Models\Enrolments\ClassList;
use App\Services\Enrolments\ClassListTransitionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class TransitionClassListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $toType = (string) $this->input('to_type');
        if ($toType === '') {
            return false;
        }

        $service = app(ClassListTransitionService::class);
        $ids = collect($this->input('application_ids', []))->map(fn ($id) => (int) $id)->filter();

        if ($ids->isEmpty()) {
            return $user->can($service->permissionForTargetType($toType));
        }

        $entries = ClassList::query()
            ->whereIn('student_application_id', $ids)
            ->get();

        if ($entries->isEmpty()) {
            return $user->can($service->permissionForTargetType($toType));
        }

        foreach ($entries as $entry) {
            $fromType = $entry->type instanceof ClassListTypeEnum
                ? $entry->type->value
                : (string) $entry->type;
            $permission = $service->permissionForTransition($fromType, $toType);
            if (! $user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['required', 'integer', 'exists:student_applications,id'],
            'to_type' => ['required', new Enum(ClassListTypeEnum::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            'bypass_ranking' => ['sometimes', 'boolean'],
            'institution_department_id' => ['nullable', 'integer', 'exists:institution_departments,id'],
            'department_level_id' => ['nullable', 'integer', 'exists:department_levels,id'],
            'department_course_id' => ['nullable', 'integer', 'exists:department_courses,id'],
            'intake_period_id' => ['nullable', 'integer', 'exists:intake_periods,id'],
            'mode_of_study_id' => ['nullable', 'integer', 'exists:mode_of_studies,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $bypass = $this->boolean('bypass_ranking');
            $toType = (string) $this->input('to_type');
            $note = trim((string) $this->input('note', ''));
            $service = app(ClassListTransitionService::class);

            if ($bypass && strlen($note) < 10) {
                $validator->errors()->add('note', 'A note of at least 10 characters is required when bypassing ranking.');
            }

            if ($toType === ClassListTypeEnum::FAILED->value && strlen($note) < 10) {
                $validator->errors()->add('note', 'A note of at least 10 characters is required when failing an application.');
            }

            $ids = collect($this->input('application_ids', []))->map(fn ($id) => (int) $id);
            $entries = ClassList::query()->whereIn('student_application_id', $ids)->get();

            foreach ($entries as $entry) {
                $fromType = $entry->type instanceof ClassListTypeEnum
                    ? $entry->type->value
                    : (string) $entry->type;

                if (! $service->isAllowed($fromType, $toType)) {
                    $validator->errors()->add('to_type', "Transition from {$fromType} to {$toType} is not allowed.");
                    break;
                }

                if ($service->isDemote($fromType, $toType) && strlen($note) < 10) {
                    $validator->errors()->add('note', 'A note of at least 10 characters is required when demoting class list status.');
                    break;
                }
            }
        });
    }

    /**
     * @return array{institution_department_id: int|null, department_level_id: int|null, department_course_id: int|null, intake_period_id: int|null, mode_of_study_id: int|null}
     */
    public function context(): array
    {
        return [
            'institution_department_id' => $this->integer('institution_department_id') ?: null,
            'department_level_id' => $this->integer('department_level_id') ?: null,
            'department_course_id' => $this->integer('department_course_id') ?: null,
            'intake_period_id' => $this->integer('intake_period_id') ?: null,
            'mode_of_study_id' => $this->integer('mode_of_study_id') ?: null,
        ];
    }
}
