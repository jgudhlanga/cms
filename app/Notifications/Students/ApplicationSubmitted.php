<?php

namespace App\Notifications\Students;

use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
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
        public string $name,
        public StudentApplication $program,
        public WorkflowStep $newStep,
        public WorkflowStep $oldStep
    ) {
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
            ->line("The status of your application with reference: **{$this->program->application_tracking_number}** has changed.")
            ->line("**Previous Status:** {$this->oldStep->name}")
            ->line("**New Status:** {$this->newStep->name}")
            ->line('You can track the progress of your application by logging into your student portal.')
            ->action('Track your application', url(route('portal.application.view', $this->program->id)))
            ->line('If you have any questions, please contact support.');
    }

    /**
     * Get the array representation of the notification.o
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
