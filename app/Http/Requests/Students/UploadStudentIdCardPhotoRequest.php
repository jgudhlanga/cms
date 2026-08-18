<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\Student;
use App\Models\Students\StudentIdCardRequest;
use Illuminate\Foundation\Http\FormRequest;

class UploadStudentIdCardPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $student = $this->route('student');
        if ($student instanceof Student) {
            return $user->can('uploadIdPhoto', $student);
        }

        return $user->can('uploadPhoto', StudentIdCardRequest::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => self::photoRules(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function photoRules(): array
    {
        $minWidth = (int) config('id_cards.photo.min_width', 200);
        $minHeight = (int) config('id_cards.photo.min_height', 240);
        $maxKb = (int) config('id_cards.photo.max_kilobytes', 2048);

        return [
            'required',
            'image',
            'mimes:jpeg,jpg,png',
            'max:'.$maxKb,
            'dimensions:min_width='.$minWidth.',min_height='.$minHeight,
        ];
    }
}
