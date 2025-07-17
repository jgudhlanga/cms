<?php

namespace App\Listeners\Students;


use App\Events\Students\ApplicationWorkflowStepChanged;
use App\Notifications\Students\ApplicationSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class ApplicationWorkflowStepAction implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ApplicationWorkflowStepChanged $event): void
    {
        $user = $event->student->user;
        $name = $user->full_name;
        $program = $event->program;
        $newStep = $event->newStep;
        $oldStep = $event->oldStep;
        $program->update([
            'department_application_step_id' => $newStep->id,
        ]);
        Notification::sendNow($user, new ApplicationSubmitted($name, $program, $newStep, $oldStep));
    }
}
