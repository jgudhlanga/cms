<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution;

use App\Rules\Institution\AcceptedCourseSyllabusImportFile;
use Illuminate\Foundation\Http\FormRequest;

class CourseSyllabusImportPreviewRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240', new AcceptedCourseSyllabusImportFile],
        ];
    }
}
