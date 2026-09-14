<?php

namespace App\Support\AcademicCalendars;

use App\Models\AcademicCalendars\CourseWorkCaptureExtension;

final class CourseWorkExtensionSummary
{
    /**
     * @return array{class: string, module: string, assessment: string, requester: string, requestedUntil: string, approvedUntil: string}
     */
    public static function for(CourseWorkCaptureExtension $extension): array
    {
        $extension->loadMissing(['academicCalendarClass', 'courseSyllabusModule', 'assessmentType', 'requester']);
        $module = $extension->courseSyllabusModule;

        return [
            'class' => (string) ($extension->academicCalendarClass?->name ?? ''),
            'module' => trim(sprintf('%s %s', (string) ($module?->code ?? ''), (string) ($module?->title ?? ''))),
            'assessment' => (string) ($extension->assessmentType?->name ?? __('academic_calendar.course_work_window_module_mark')),
            'requester' => (string) ($extension->requester?->full_name ?? ''),
            'requestedUntil' => $extension->requested_until?->format('d M Y') ?? '',
            'approvedUntil' => $extension->approved_until?->format('d M Y') ?? '',
        ];
    }
}
