<?php

namespace App\Notifications\Assessments;

use App\Models\AcademicCalendars\CourseWorkProgressReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseWorkProgressReportSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CourseWorkProgressReport $report) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->full_name ?? $notifiable->email;
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting("Hello {$name},")
            ->line($this->body());

        if (filled($this->report->notes)) {
            $mail->line(__('academic_calendar.course_work_extension_note_line', ['note' => $this->report->notes]));
        }

        return $mail->action(__('academic_calendar.course_work_progress_reports_title'), route('course-work-progress-reports.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'course_work_progress_report_submitted',
            'reportId' => (int) $this->report->id,
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => route('course-work-progress-reports.index'),
        ];
    }

    private function title(): string
    {
        return __('academic_calendar.course_work_progress_report_submitted_title', [
            'programme' => (string) ($this->report->snapshot['programme'] ?? ''),
        ]);
    }

    private function body(): string
    {
        $this->report->loadMissing('submitter');
        $totals = $this->report->snapshot['totals'] ?? [];

        return __('academic_calendar.course_work_progress_report_submitted_body', [
            'lecturer' => (string) ($this->report->submitter?->full_name ?? ''),
            'captured' => (int) ($totals['captured'] ?? 0),
            'expected' => (int) ($totals['expected'] ?? 0),
            'missing' => (int) ($totals['missing'] ?? 0),
        ]);
    }
}
