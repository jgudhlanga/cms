<?php

declare(strict_types=1);

namespace App\Http\Requests\HMS;

use Illuminate\Foundation\Http\FormRequest;

class HostelOccupantImportProcessRequest extends FormRequest
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
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.rowNumber' => ['required', 'integer', 'min:1'],
            'rows.*.studentId' => ['required', 'integer', 'exists:students,id'],
            'rows.*.disability' => ['nullable', 'string', 'max:32'],
            'rows.*.hostelRoomId' => ['required', 'integer', 'exists:hostel_rooms,id'],
            'rows.*.hostelRoomSectionId' => ['required', 'integer', 'exists:hostel_room_sections,id'],
        ];
    }
}
