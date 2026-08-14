<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Rules\Maintenance\AcceptedSponsoredStudentImportFile;
use Illuminate\Foundation\Http\FormRequest;

class SponsoredStudentImportPreviewRequest extends FormRequest
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
            'file' => ['required', 'file', new AcceptedSponsoredStudentImportFile],
            'calendar_year' => ['required', 'integer', 'min:'.($currentYear - 10), 'max:'.($currentYear + 10)],
        ];
    }
}
