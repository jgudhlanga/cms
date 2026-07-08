<?php

namespace App\Http\Requests\HMS;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncHostelRoomSectionAmenitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => [
                'integer',
                Rule::exists('hostel_amenities', 'id'),
            ],
        ];
    }
}
