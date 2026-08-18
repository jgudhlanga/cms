<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\StudentIdCardRequest;
use App\Rules\Students\AcceptedStudentIdCardImportFile;
use Illuminate\Foundation\Http\FormRequest;

class StudentIdCardImportPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('import', StudentIdCardRequest::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', new AcceptedStudentIdCardImportFile],
        ];
    }
}
