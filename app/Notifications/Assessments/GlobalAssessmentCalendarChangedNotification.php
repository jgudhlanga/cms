<?php

namespace App\Notifications\Assessments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a head of department that a change to the college-wide window moved their department's dates.
 */
class GlobalAssessmentCalendarChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $assessmentName,
        public string $departmentName,
        public int $institutionDepartmentId,
        public string $globalStartDate,
        public string $globalEndDate,
        public string $departmentStartDate,
        public string $departmentEndDate,
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
            ->action(__('academic_calendar.department_assessment_calendar_title'), $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'global_assessment_calendar_changed',
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return route('department-assessment-calendars.index', ['department' => $this->institutionDepartmentId]);
    }

    private function title(): string
    {
        return __('academic_calendar.global_assessment_calendar_changed_title', ['assessment' => $this->assessmentName]);
    }

    private function body(): string
    {
        return __('academic_calendar.global_assessment_calendar_changed_body', [
            'assessment' => $this->assessmentName,
            'start_date' => $this->globalStartDate,
            'end_date' => $this->globalEndDate,
            'department_start' => $this->departmentStartDate,
            'department_end' => $this->departmentEndDate,
        ]);
    }
}
