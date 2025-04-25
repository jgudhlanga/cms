<?php

namespace App\Http\Requests\Statuses;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $id
 * @property mixed $status
 */
class StatusRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
           'title' => ['required', 'string', 'max:255', 'unique:statuses,title,'.$this->status?->id]
        ];
    }
}
