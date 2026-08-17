<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\StudentIdCardSetting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentIdCardSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        return $user->can('update', StudentIdCardSetting::resolveForTenant());
    }

    public function rules(): array
    {
        return [
            'institution_name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'return_name' => ['required', 'string', 'max:255'],
            'return_address' => ['required', 'string', 'max:500'],
            'return_phone' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'principal_signature' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
        ];
    }
}
