<?php

namespace App\Http\Requests\Acl;

use App\Support\Dashboard\DashboardTab;
use Illuminate\Foundation\Http\FormRequest;

class ModuleSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $module = $this->route('module');
        $rules = [
            'status' => ['required', 'boolean'],
        ];

        if ($module?->slug === 'dashboards') {
            $rules['settings'] = ['required', 'array'];
            $rules['settings.tabs'] = ['required', 'array'];

            foreach (DashboardTab::cases() as $tab) {
                $rules['settings.tabs.'.$tab->value] = ['required', 'boolean'];
            }
        }

        return $rules;
    }
}
