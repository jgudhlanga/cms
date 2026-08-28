<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Support\Maintenance\MaintenanceExportFilters;
use Illuminate\Foundation\Http\FormRequest;

class StudentEnrollmentExportPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return MaintenanceExportFilters::enrolmentRules();
    }

    /**
     * @return array<string, mixed>
     */
    public function exportFilters(): array
    {
        return MaintenanceExportFilters::normalizeForEnrolments($this->validated());
    }
}
