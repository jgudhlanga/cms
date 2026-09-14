<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Approve, reject and revoke share this request; the ability checked follows the route action.
 */
class CourseWorkExtensionDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $extension = $this->route('course_work_capture_extension');

        if (! $extension instanceof CourseWorkCaptureExtension) {
            return false;
        }

        $ability = match (true) {
            $this->routeIs('course-work-extensions.approve') => 'approve',
            $this->routeIs('course-work-extensions.revoke') => 'revoke',
            default => 'reject',
        };

        return $this->user()?->can($ability, $extension) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'approved_until' => [$this->routeIs('course-work-extensions.approve') ? 'required' : 'nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
