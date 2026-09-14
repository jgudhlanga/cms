<?php

namespace App\Notifications\Assessments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssessmentWindowOpenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $assessmentName,
        public string $departmentName,
        public string $endDate,
        public ?string $notes = null,
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
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting("Hello {$name},")
            ->line($this->body());

        if (filled($this->notes)) {
            $mail->line(__('academic_calendar.course_work_extension_note_line', ['note' => $this->notes]));
        }

        return $mail->action(__('academic_calendar.course_work_window_open_classes_action'), route('teaching.classes.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'assessment_window_opened',
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => route('teaching.classes.index'),
        ];
    }

    private function title(): string
    {
        return __('academic_calendar.course_work_window_opened_title', ['assessment' => $this->assessmentName]);
    }

    private function body(): string
    {
        return __('academic_calendar.course_work_window_opened_body', [
            'assessment' => $this->assessmentName,
            'department' => $this->departmentName,
            'end_date' => $this->endDate,
        ]);
    }
}
