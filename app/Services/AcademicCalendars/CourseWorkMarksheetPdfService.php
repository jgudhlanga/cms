<?php

namespace App\Services\AcademicCalendars;

class CourseWorkMarksheetPdfService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function assembleViewData(array $data): array
    {
        return [
            'header' => $data['header'] ?? [],
            'assessmentTypes' => $data['assessmentTypes'] ?? [],
            'rows' => $data['rows'] ?? [],
            'summary' => $data['summary'] ?? [],
            'issues' => $data['issues'] ?? [],
        ];
    }
}
