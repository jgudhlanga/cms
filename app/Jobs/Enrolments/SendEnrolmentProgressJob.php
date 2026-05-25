<?php

namespace App\Jobs\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Mail\Enrolments\ProvisionalClassListMail;
use App\Mail\Enrolments\RejectedApplicationMail;
use App\Mail\Enrolments\WaitingClassListMail;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Shared\WorkflowStep;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEnrolmentProgressJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $classId,
        protected string $type,
        protected string $institutionDepartmentId,
        protected ?string $department = null,
        protected ?string $level = null,
        protected ?string $course = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $details = $this->fetchApplicationDetails();

        if (! $details) {
            // Optionally, you could log missing record:
            Log::warning("ClassList not found for ID {$this->classId}");

            return;
        }

        $mailable = $this->resolveMailable(
            name: "{$details->first_name} {$details->last_name}",
            department: $this->department,
            level: $this->level, course: $this->course
        );
        $email = $details->email;
        if ($mailable) {
            $type = match ($this->type) {
                ClassListTypeEnum::PROVISIONAL->value => WorkflowStepEnum::REQUIREMENTS->slug(),
                ClassListTypeEnum::WAITING->value => WorkflowStepEnum::WAITLISTED->slug(),
                ClassListTypeEnum::FAILED->value => WorkflowStepEnum::REJECTED->slug(),
                default => null,
            };
            $step = WorkflowStep::where('slug', $type)->first();
            $departmentStep = DepartmentApplicationStep::where('institution_department_id', $this->institutionDepartmentId)->where('workflow_step_id', $step->id)->first();
            DB::table('student_programs')->where('id', $details->application_id)->update(['department_application_step_id' => $departmentStep->id]);
            Mail::to($email)->send($mailable);
        }
    }

    /**
     * Get application + user details for the given class ID.
     */
    protected function fetchApplicationDetails(): ?object
    {
        return DB::table('class_lists as cl')
            ->join('student_programs as sp', 'sp.id', '=', 'cl.student_program_id')
            ->join('students as st', 'st.id', '=', 'sp.student_id')
            ->join('users as us', 'us.id', '=', 'st.user_id')
            ->where('cl.id', $this->classId)
            ->select([
                'sp.id as application_id',
                'us.first_name',
                'us.last_name',
                'us.email',
            ])
            ->first();
    }

    /**
     * Resolve which mailable to send based on the class list type.
     */
    protected function resolveMailable(string $name, ?string $department = null, ?string $level = null, ?string $course = null): ?Mailable
    {
        return match ($this->type) {
            ClassListTypeEnum::PROVISIONAL->value => new ProvisionalClassListMail($name, $department, $level, $course),
            ClassListTypeEnum::WAITING->value => new WaitingClassListMail($name, $department, $level, $course),
            ClassListTypeEnum::FAILED->value => new RejectedApplicationMail($name),
            default => null,
        };
    }
}
