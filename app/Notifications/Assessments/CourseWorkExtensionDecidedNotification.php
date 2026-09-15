<?php

namespace App\Notifications\Assessments;

use App\Enums\AcademicCalendars\CourseWorkExtensionStatusEnum;
use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use App\Support\AcademicCalendars\CourseWorkExtensionSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseWorkExtensionDecidedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CourseWorkCaptureExtension $extension) {}

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
            ->line($this->body($summary));

        if (filled($this->extension->decision_note)) {
            $mail->line(__('academic_calendar.course_work_extension_note_line', ['note' => $this->extension->decision_note]));
        }

        return $mail->action(__('academic_calendar.course_work_extension_view_class_action'), $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $summary = CourseWorkExtensionSummary::for($this->extension);

        return [
            'kind' => 'course_work_extension_'.$this->extension->status->value,
            'extensionId' => (int) $this->extension->id,
            'status' => $this->extension->status->value,
            'title' => $this->title($summary),
            'body' => $this->body($summary),
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return route('teaching.classes.show', $this->extension->academic_calendar_class_id);
    }

    /**
     * @param  array<string, string>  $summary
     */
    private function title(array $summary): string
    {
        return __('academic_calendar.course_work_extension_decided_title_'.$this->statusKey(), [
            'assessment' => $summary['assessment'],
            'class' => $summary['class'],
        ]);
    }

    /**
     * @param  array<string, string>  $summary
     */
    private function body(array $summary): string
    {
        return __('academic_calendar.course_work_extension_decided_body_'.$this->statusKey(), [
            'assessment' => $summary['assessment'],
            'module' => $summary['module'],
            'class' => $summary['class'],
            'date' => $summary['approvedUntil'],
        ]);
    }

    private function statusKey(): string
    {
        return match ($this->extension->status) {
            CourseWorkExtensionStatusEnum::Approved => 'approved',
            CourseWorkExtensionStatusEnum::Revoked => 'revoked',
            default => 'rejected',
        };
    }
}
