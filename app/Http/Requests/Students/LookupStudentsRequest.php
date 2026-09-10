<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Users\User;
use Illuminate\Foundation\Http\FormRequest;

class LookupStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can('viewAny:students') || $user->can('view:students');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'institution_department_id' => ['nullable', 'integer'],
            'department_level_id' => ['nullable', 'integer'],
            'department_course_id' => ['nullable', 'integer', 'required_without_all:name,search'],
            'name' => ['nullable', 'string', 'min:2', 'max:100', 'required_without_all:search,department_course_id'],
            'search' => ['nullable', 'string', 'min:2', 'max:100', 'required_without_all:name,department_course_id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $search = $this->input('search');

        $this->merge([
            'name' => is_string($name) && trim($name) !== '' ? $name : null,
            'search' => is_string($search) && trim($search) !== '' ? $search : null,
        ]);
    }
}
