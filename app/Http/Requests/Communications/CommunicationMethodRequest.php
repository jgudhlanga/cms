<?php

namespace App\Http\Requests\Communications;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $communication_method
 */
class CommunicationMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'unique:communication_methods,title,'.$this->communication_method?->id],
        ];
    }
}
