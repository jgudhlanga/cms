<?php

namespace App\Jobs\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use App\Mail\Enrolments\ProvisionalClassListMail;
use App\Mail\Enrolments\RejectedApplicationMail;
use App\Mail\Enrolments\WaitingClassListMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;

class SendEnrolmentProgressJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int    $classId,
        protected string $type,
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $details = $this->fetchApplicationDetails();

        if (!$details) {
            // Optionally, you could log missing record:
            Log::warning("ClassList not found for ID {$this->classId}");
            return;
        }

        $mailable = $this->resolveMailable(
            name: "{$details->first_name} {$details->last_name}"
        );
        //$email = $details->email;
        $email = 'jimmyneds@gmail.com';

        if ($mailable) {
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
    protected function resolveMailable(string $name): ?Mailable
    {
        return match ($this->type) {
            ClassListTypeEnum::PROVISIONAL->value => new ProvisionalClassListMail($name),
            ClassListTypeEnum::WAITING->value => new WaitingClassListMail($name),
            ClassListTypeEnum::FAILED->value => new RejectedApplicationMail($name),
            default => null,
        };
    }
}
