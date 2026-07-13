<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\Student;
use Illuminate\Foundation\Http\FormRequest;

class PurgeStudentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        if (! $student instanceof Student) {
            return false;
        }

        $authUser = $this->user();

        return $authUser !== null
            && $authUser->can('root:manage')
            && (int) $student->tenant_id === (int) $authUser->tenant_id;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
