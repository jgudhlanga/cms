<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;

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
            'email' => ['required', 'string', 'max:255', 'email', 'unique:users,email,' . $userId],
            'phone_number' => ['required', 'nullable', 'max:30', 'unique:users,phone_number,' . $userId],
            'role_ids' => ['nullable', 'array'],
            'institution_department_id' => ['nullable', 'integer', 'exists:institution_departments,id'],
        ];
    }

}
