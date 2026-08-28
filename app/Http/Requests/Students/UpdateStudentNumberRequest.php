<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\Student;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        if (! $student instanceof Student) {
            return false;
        }

        return $this->user()?->can('changeStudentNumber', $student) ?? false;
    }

    public function prepareForValidation(): void
    {
        if ($this->has('student_number')) {
            $this->merge([
                'student_number' => EnrollmentLookupService::normalizeStudentNumber((string) $this->input('student_number')),
            ]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'student_number' => ['required', 'string', 'max:50'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
