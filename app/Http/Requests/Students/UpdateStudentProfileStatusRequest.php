<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\Student;
use App\Support\Students\StudentApplicationStatusMapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentProfileStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        if (! $student instanceof Student) {
            return false;
        }

        return $this->user()?->can('changeStudentStatus', $student) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(app(StudentApplicationStatusMapper::class)->allowedSlugs())],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => __('students.change_status_invalid'),
        ];
    }
}
