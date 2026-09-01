<?php

namespace App\Http\Requests\Institution;

use App\Models\Institution\InstitutionDepartment;
use App\Models\Rbac\Role;
use App\Support\Institution\DepartmentStaffRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Validator;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->role_ids)) {
            $this->merge([
                'role_ids' => json_decode($this->role_ids, true),
            ]);
        }

    }

    public function rules(): array
    {
        $userId = ($this->staff->user->id ?? 'NULL');
        if (Route::currentRouteName() === 'users.update-staff-user') {
            $userId = $this->route('user')->id ?? 'NULL';
        }

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:255'],
            'gender_id' => ['required', 'integer', 'exists:genders,id'],
            'marital_status_id' => ['required', 'integer', 'exists:marital_statuses,id'],
            'title_id' => ['required', 'integer', 'exists:titles,id'],
            'email' => ['required', 'string', 'max:255', 'email', 'unique:users,email,'.$userId],
            'phone_number' => ['required', 'nullable', 'max:30', 'unique:users,phone_number,'.$userId],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'institution_department_id' => ['nullable', 'integer', 'exists:institution_departments,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $department = $this->route('department');

            if (! $department instanceof InstitutionDepartment) {
                return;
            }

            $roleIds = $this->input('role_ids', []);

            if ($roleIds === [] || $roleIds === null) {
                return;
            }

            $allowedSlugs = DepartmentStaffRoles::allowedSlugsFor($department);
            $allowedIds = Role::query()
                ->whereIn('slug', $allowedSlugs)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            foreach ($roleIds as $roleId) {
                if (! in_array((int) $roleId, $allowedIds, true)) {
                    $validator->errors()->add(
                        'role_ids',
                        'One or more selected roles are not allowed for this department.',
                    );

                    return;
                }
            }
        });
    }
}
