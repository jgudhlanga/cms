<?php

namespace App\Jobs\HMS;

use App\Mail\HMS\HostelApplicationDeclinedMail;
use App\Models\HMS\HostelApplication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendHostelApplicationDeclinedEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(protected int $hostelApplicationId) {}

    public function handle(): void
    {
        $application = HostelApplication::query()
            ->with(['student.user'])
            ->find($this->hostelApplicationId);

        if ($application === null) {
            return;
        }

        $email = $application->student?->user?->email ?? $application->email_address;

        if (blank($email)) {
            return;
        }

        $name = $application->student?->user?->full_name
            ?? $application->name
            ?? 'Applicant';

        Mail::to($email)->send(new HostelApplicationDeclinedMail(
            $name,
            (string) $application->decline_reason,
        ));
    }
}
