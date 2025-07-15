<?php

namespace App\Events\Students;

use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationWorkflowStepChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Student                    $student,
        public StudentProgram             $program,
        public DepartmentApplicationStep  $newStep,
        public ?DepartmentApplicationStep $oldStep = null)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
