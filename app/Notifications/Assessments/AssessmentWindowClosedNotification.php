<?php

namespace App\Notifications\Assessments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once when a department's capture window closes with marks still missing: lecturers are told
 * capture is locked and how to ask for an extension; heads of department get the summary.
 */
class AssessmentWindowClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $assessmentName,
        public string $departmentName,
        public string $endDate,
        public int $incompleteCount,
        public bool $forLeadership = false,
    ) {}

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

        return (new MailMessage)
            ->subject($this->title())
            ->greeting("Hello {$name},")
            ->line($this->body())
            ->action($this->actionLabel(), $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'assessment_window_closed',
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'incompleteCount' => $this->incompleteCount,
        ];
    }

    private function title(): string
    {
        return __('academic_calendar.course_work_window_closed_title', ['assessment' => $this->assessmentName]);
    }

    private function body(): string
    {
        $replace = [
            'assessment' => $this->assessmentName,
            'department' => $this->departmentName,
            'end_date' => $this->endDate,
            'count' => $this->incompleteCount,
        ];

        return $this->forLeadership
            ? __('academic_calendar.course_work_window_closed_body_leadership', $replace)
            : __('academic_calendar.course_work_window_closed_body_lecturer', $replace);
    }

    private function actionLabel(): string
    {
        return $this->forLeadership
            ? __('assessments.dashboard_view_missing_marks_report')
            : __('academic_calendar.course_work_window_open_classes_action');
    }

    private function url(): string
    {
        return $this->forLeadership ? route('missing-marks-report.index') : route('teaching.classes.index');
    }
}
