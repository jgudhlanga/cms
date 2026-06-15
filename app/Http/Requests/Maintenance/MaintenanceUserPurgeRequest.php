<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Models\Users\User;
use Illuminate\Foundation\Http\FormRequest;

class MaintenanceUserPurgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        if (! $user instanceof User) {
            return false;
        }

        $authUser = $this->user();

        return $authUser !== null && (int) $user->tenant_id === (int) $authUser->tenant_id;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }
}
