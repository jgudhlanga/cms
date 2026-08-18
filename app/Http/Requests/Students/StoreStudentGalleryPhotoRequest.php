<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\Student;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentGalleryPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $student = $user?->studentProfile;

        return $user !== null
            && $student instanceof Student
            && $user->can('manageGallery', $student);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => UploadStudentIdCardPhotoRequest::photoRules(),
        ];
    }
}
