<?php

namespace App\Services\AcademicCalendars;

use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkProgressReport;
use App\Models\Users\User;
use App\Notifications\Assessments\CourseWorkProgressReportAcknowledgedNotification;
use App\Notifications\Assessments\CourseWorkProgressReportSubmittedNotification;
use App\Support\Institution\DepartmentLeadershipResolver;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * The Lecturer in Charge freezes the current capture progress for a programme and sends it to the HOD;
 * the HOD acknowledges it with an optional comment.
 */
class CourseWorkProgressReportService
{
    public function __construct(
        private readonly CourseWorkProgressService $progress,
        private readonly DepartmentLeadershipResolver $leadership,
    ) {}

    public function submit(User $submitter, ClassConfig $classConfig, ?string $notes = null): CourseWorkProgressReport
    {
        $classConfig->loadMissing('institutionDepartment');
        $snapshot = $this->progress->forClassConfig($classConfig);
        unset($snapshot['lastReport']);

        $report = CourseWorkProgressReport::query()->create([
            'tenant_id' => $classConfig->institutionDepartment?->tenant_id ?? $submitter->tenant_id,
            'class_config_id' => $classConfig->id,
            'institution_department_id' => $classConfig->institution_department_id,
            'academic_calendar_id' => $snapshot['academicCalendarId'] ?? null,
            'submitted_by' => $submitter->id,
            'snapshot' => $snapshot,
            'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            'submitted_at' => now(),
        ]);

        $heads = $this->leadership
            ->headsOfDepartment((int) $classConfig->institution_department_id)
            ->reject(fn (User $head): bool => (int) $head->id === (int) $submitter->id);

        if ($heads->isNotEmpty()) {
            Notification::send($heads, new CourseWorkProgressReportSubmittedNotification($report));
        }

        return $report;
    }

    public function acknowledge(User $head, CourseWorkProgressReport $report, ?string $comment = null): CourseWorkProgressReport
    {
        if ($report->acknowledged_at !== null) {
            throw ValidationException::withMessages([
                'report' => [__('academic_calendar.course_work_progress_already_acknowledged')],
            ]);
        }

        $report->update([
            'acknowledged_by' => $head->id,
            'acknowledged_at' => now(),
            'hod_comment' => $comment !== null && trim($comment) !== '' ? trim($comment) : null,
        ]);

        $submitter = $report->submitter()->first();

        if ($submitter instanceof User) {
            $submitter->notify(new CourseWorkProgressReportAcknowledgedNotification($report));
        }

        return $report->refresh();
    }
}
