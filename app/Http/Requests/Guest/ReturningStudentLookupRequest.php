<?php

namespace App\Http\Requests\Guest;

use App\Rules\ZimbabweanIdNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturningStudentLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type', 'id_number');

        $valueRules = ['required', 'string', 'max:50'];

        if ($type === 'id_number') {
            $valueRules[] = new ZimbabweanIdNumber;
        }

        return [
            'type' => ['required', 'string', Rule::in(['id_number', 'student_number'])],
            'value' => $valueRules,
        ];
    }
}
