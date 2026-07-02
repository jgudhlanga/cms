<?php

namespace App\Services\AcademicCalendars;

use App\Helpers\DocumentHelper;

class ClassListPdfService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function assembleViewData(array $data, ?int $tenantId = null): array
    {
        return [
            'documentTemplate' => DocumentHelper::resolvePdfHeaderTemplate($tenantId),
            'institutionName' => $data['institutionName'] ?? (string) config('app.display_name'),
            'sections' => $data['sections'] ?? [],
        ];
    }
}
