<?php

namespace App\Notifications\Assessments;

use App\Models\AcademicCalendars\CourseWorkProgressReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseWorkProgressReportAcknowledgedNotification extends Notification implements ShouldQueue
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

        if (filled($this->report->hod_comment)) {
            $mail->line(__('academic_calendar.course_work_extension_note_line', ['note' => $this->report->hod_comment]));
        }

        return $mail->action(__('academic_calendar.course_work_progress_title'), $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'course_work_progress_report_acknowledged',
            'reportId' => (int) $this->report->id,
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return route('teaching.course-work-progress.show', ['class_config' => $this->report->class_config_id]);
    }

    private function title(): string
    {
        return __('academic_calendar.course_work_progress_report_acknowledged_title', [
            'programme' => (string) ($this->report->snapshot['programme'] ?? ''),
        ]);
    }

    private function body(): string
    {
        $this->report->loadMissing('acknowledger');

        return __('academic_calendar.course_work_progress_report_acknowledged_body', [
            'hod' => (string) ($this->report->acknowledger?->full_name ?? ''),
        ]);
    }
}
