<?php

namespace App\Listeners\Students;


use App\Events\Students\ApplicationWorkflowStepChanged;
use App\Notifications\Students\ApplicationSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        $student = $event->student;
        $program = $event->program;
        $newStep = $event->newStep;
        $oldStep = $event->oldStep;

        $user->notify(new ApplicationSubmitted(
            $name,
            $program,
        ))->withoutDelay();
    }
}
