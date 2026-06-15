<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Rules\Maintenance\AcceptedStaffImportFile;
use Illuminate\Foundation\Http\FormRequest;

class StaffImportPreviewRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240', new AcceptedStaffImportFile],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('trans.maintenance_staff_import_file_required'),
            'file.max' => __('trans.maintenance_staff_import_file_too_large'),
        ];
    }
}
