<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\StudentIdCardRequest;
use Illuminate\Foundation\Http\FormRequest;

class UploadStudentIdCardPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('uploadPhoto', StudentIdCardRequest::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png',
                'max:2048',
                'dimensions:min_width=200,min_height=240',
            ],
        ];
    }
}
