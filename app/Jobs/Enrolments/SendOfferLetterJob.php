<?php

namespace App\Jobs\Enrolments;

use App\Mail\Enrolments\VerifiedStudentsOfferLetterMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOfferLetterJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $name, protected string $email, protected string $applicationId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $email = 'jimmyneds@gmail.com'; //$this->email;
        $name = $this->name;
        $applicationId = $this->applicationId;
        Mail::to($email)->send(
            new VerifiedStudentsOfferLetterMail($name, $applicationId)
        );
    }
}
