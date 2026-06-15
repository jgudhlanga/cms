<?php

namespace App\Http\Requests\HMS;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHostelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'warden_id' => ['nullable', 'integer', Rule::exists('staff', 'id')],
            'location' => ['nullable', 'string', 'max:255'],
            'floor_count' => ['required', 'integer', 'min:0'],
            'rooms_count' => ['required', 'integer', 'min:0'],
            'capacity' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'type' => ['nullable', Rule::in(['male', 'female', 'mixed'])],
            'description' => ['nullable', 'string'],
        ];
    }
}

