<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentIntakePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        if (! $student instanceof Student) {
            return false;
        }

        return $this->user()?->can('changeIntakePeriod', $student) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'intake_period_id' => ['required', 'integer', Rule::exists('intake_periods', 'id')],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
