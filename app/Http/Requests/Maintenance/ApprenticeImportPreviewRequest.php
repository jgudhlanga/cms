<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Rules\Maintenance\AcceptedApprenticeImportFile;
use Illuminate\Foundation\Http\FormRequest;

class ApprenticeImportPreviewRequest extends FormRequest
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
        $currentYear = (int) now()->format('Y');

        return [
            'file' => ['required', 'file', new AcceptedApprenticeImportFile],
            'institution_department_id' => ['required', 'integer', 'exists:institution_departments,id'],
            'calendar_year' => ['required', 'integer', 'min:'.($currentYear - 10), 'max:'.($currentYear + 10)],
        ];
    }
}
