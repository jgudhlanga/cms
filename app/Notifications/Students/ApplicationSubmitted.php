<?php

namespace App\Notifications\Students;

use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Students\StudentProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationSubmitted extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string                    $name,
        public StudentProgram            $program,
        public DepartmentApplicationStep $newStep,
        public DepartmentApplicationStep $oldStep
    )
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject('Application Status: Update')
            ->greeting("Hello {$this->name},")
            ->line("The status of your application (Reference: {$this->program->application_tracking_number}) has changed.")
            ->line("**Previous Status:** {$this->oldStep->workflowStep->name}")
            ->line("**New Status:** {$this->newStep->workflowStep->name}")
            ->action('Track Application', url("/portal/applications/{$this->program->id}"))
            ->line('If you have any questions, please contact support.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
