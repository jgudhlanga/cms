<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Enums\Students\StudentClearanceSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentClearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->has('sections')) {
            $sections = $this->input('sections');

            if (! is_array($sections) || $sections === []) {
                return false;
            }

            foreach ($sections as $row) {
                $section = StudentClearanceSection::tryFrom((string) (is_array($row) ? ($row['section'] ?? '') : ''));

                if ($section === null || ! ($this->user()?->can($section->permission()) ?? false)) {
                    return false;
                }
            }

            return true;
        }

        $section = StudentClearanceSection::tryFrom((string) $this->input('section'));

        if ($section === null) {
            return false;
        }

        return $this->user()?->can($section->permission()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $base = [
            'calendar_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
        ];

        if ($this->has('sections')) {
            return array_merge($base, [
                'sections' => ['required', 'array', 'min:1'],
                'sections.*.section' => ['required', 'string', Rule::enum(StudentClearanceSection::class)],
                'sections.*.cleared' => ['required', 'boolean'],
                'sections.*.notes' => ['nullable', 'string', 'max:2000'],
            ]);
        }

        return array_merge($base, [
            'section' => ['required', 'string', Rule::enum(StudentClearanceSection::class)],
            'cleared' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
