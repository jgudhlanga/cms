<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use Illuminate\Foundation\Http\FormRequest;

class CourseWorkExtensionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('request', CourseWorkCaptureExtension::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'assessment_type_id' => ['nullable', 'integer', 'exists:assessment_types,id'],
            'requested_until' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }
}
