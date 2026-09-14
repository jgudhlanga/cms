<?php

namespace App\Notifications\Assessments;

use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use App\Support\AcademicCalendars\CourseWorkExtensionSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseWorkExtensionRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CourseWorkCaptureExtension $extension,
        public bool $beyondGlobalDeadline = false,
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
        $summary = CourseWorkExtensionSummary::for($this->extension);
        $name = $notifiable->full_name ?? $notifiable->email;

        $mail = (new MailMessage)
            ->subject($this->title($summary))
            ->greeting("Hello {$name},")
            ->line($this->body($summary))
            ->line(__('academic_calendar.course_work_extension_reason_line', ['reason' => $this->extension->reason]));

        if ($this->beyondGlobalDeadline) {
            $mail->line(__('academic_calendar.course_work_extension_requested_beyond_global'));
        }

        return $mail->action(__('academic_calendar.course_work_extension_review_action'), route('course-work-extensions.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $summary = CourseWorkExtensionSummary::for($this->extension);

        return [
            'kind' => 'course_work_extension_requested',
            'extensionId' => (int) $this->extension->id,
            'title' => $this->title($summary),
            'body' => $this->body($summary),
            'url' => route('course-work-extensions.index'),
        ];
    }

    /**
     * @param  array<string, string>  $summary
     */
    private function title(array $summary): string
    {
        return __('academic_calendar.course_work_extension_requested_title', [
            'assessment' => $summary['assessment'],
            'class' => $summary['class'],
        ]);
    }

    /**
     * @param  array<string, string>  $summary
     */
    private function body(array $summary): string
    {
        return __('academic_calendar.course_work_extension_requested_body', [
            'requester' => $summary['requester'],
            'assessment' => $summary['assessment'],
            'module' => $summary['module'],
            'class' => $summary['class'],
            'date' => $summary['requestedUntil'],
        ]);
    }
}
