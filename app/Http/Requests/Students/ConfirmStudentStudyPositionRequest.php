<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\Student;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmStudentStudyPositionRequest extends FormRequest
{
    /**
     * Department reach is checked per enrolment in the controller.
     */
    public function authorize(): bool
    {
        $student = $this->route('student');

        if (! $student instanceof Student) {
            return false;
        }

        return $this->user()?->can('confirmStudyPosition', $student) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'positions' => ['required', 'array', 'min:1', 'max:10'],
            'positions.*.student_enrolment_id' => ['required', 'integer', 'distinct'],
            'positions.*.programme_semester_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
