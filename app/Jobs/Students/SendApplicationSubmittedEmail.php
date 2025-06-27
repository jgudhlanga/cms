<?php

namespace App\Jobs\Students;

use App\Mail\Students\ApplicationSubmittedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendApplicationSubmittedEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected string $name, protected string $email, string $trackingNumber)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $email = $this->email;
        $name = $this->name;
        $trackingNumber = $this->trackingNumber;
        Mail::to($email)->send(
            new ApplicationSubmittedMail($name, $email, $trackingNumber)
        );
    }
}
