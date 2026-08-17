<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\StudentIdCardRequest;
use Illuminate\Foundation\Http\FormRequest;

class RejectStudentIdCardRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('idCardRequest');

        if (! $request instanceof StudentIdCardRequest) {
            return $this->user()?->can('review', StudentIdCardRequest::class) ?? false;
        }

        return $this->user()?->can('review', $request) ?? false;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
