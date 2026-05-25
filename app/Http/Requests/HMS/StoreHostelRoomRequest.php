<?php

namespace App\Http\Requests\HMS;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHostelRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hostel_id'    => ['required', 'integer', Rule::exists('hostels', 'id')],
            'name'         => ['required', 'string', 'max:255'],
            'room_type'    => ['required', Rule::in(['single', 'double', 'triple', 'suite'])],
            'capacity'     => ['required', 'integer', 'min:1'],
            'status'       => ['required', Rule::in(['vacant', 'occupied', 'maintenance'])],
            'max_occupancy'=> ['required', 'integer', 'min:1'],
            'floor_number' => ['nullable', 'integer', 'min:0'],
            'description'  => ['nullable', 'string'],
        ];
    }
}
