<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Models\Institution\InstitutionDepartment;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Foundation\Http\FormRequest;

class AssignLecturerInChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('institution_department');
        $user = $this->user();

        return $department instanceof InstitutionDepartment
            && $user !== null
            && $user->can('assign:lecturer-in-charge')
            && UserAccessScope::for($user)->canReachDepartment((int) $department->id);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
        ];
    }
}
