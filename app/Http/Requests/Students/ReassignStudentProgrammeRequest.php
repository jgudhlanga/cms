<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\DTO\Students\ReassignStudentProgrammeDto;
use App\Services\Students\ProgrammeOfferingGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class ReassignStudentProgrammeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->can('update:student-applications')
            || $user->can('manage:data-maintenance')
            || $user->can('root:manage')
        );
    }

    public function rules(): array
    {
        return [
            'application_ids' => ['nullable', 'array'],
            'application_ids.*' => ['integer', 'distinct', 'exists:student_applications,id'],
            'student_enrolment_ids' => ['nullable', 'array'],
            'student_enrolment_ids.*' => ['integer', 'distinct', 'exists:student_enrolments,id'],
            'institution_department_id' => ['required', 'integer', 'exists:institution_departments,id'],
            'department_level_id' => ['required', 'integer', 'exists:department_levels,id'],
            'department_course_id' => ['required', 'integer', 'exists:department_courses,id'],
            'mode_of_study_id' => ['required', 'integer', 'exists:mode_of_studies,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $applicationIds = array_map('intval', $this->input('application_ids', []) ?? []);
            $enrolmentIds = array_map('intval', $this->input('student_enrolment_ids', []) ?? []);

            if ($applicationIds === [] && $enrolmentIds === []) {
                $validator->errors()->add('application_ids', __('students.reassign_programme_ids_required'));

                return;
            }

            try {
                app(ProgrammeOfferingGuard::class)->assert(ReassignStudentProgrammeDto::fromArray([
                    'institution_department_id' => $this->integer('institution_department_id'),
                    'department_level_id' => $this->integer('department_level_id'),
                    'department_course_id' => $this->integer('department_course_id'),
                    'mode_of_study_id' => $this->integer('mode_of_study_id'),
                ]));
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }
        });
    }

    public function target(): ReassignStudentProgrammeDto
    {
        return ReassignStudentProgrammeDto::fromArray($this->validated());
    }

    /**
     * @return list<int>
     */
    public function applicationIds(): array
    {
        return array_values(array_filter(array_map('intval', $this->input('application_ids', []) ?? [])));
    }

    /**
     * @return list<int>
     */
    public function studentEnrolmentIds(): array
    {
        return array_values(array_filter(array_map('intval', $this->input('student_enrolment_ids', []) ?? [])));
    }
}
