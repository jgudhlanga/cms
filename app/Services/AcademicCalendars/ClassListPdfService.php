<?php

namespace App\Services\AcademicCalendars;

class ClassListPdfService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function assembleViewData(array $data): array
    {
        return [
            'institutionName' => $data['institutionName'] ?? (string) config('app.display_name'),
            'sections' => $data['sections'] ?? [],
        ];
    }
}
