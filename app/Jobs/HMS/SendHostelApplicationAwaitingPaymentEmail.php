<?php

namespace App\Jobs\HMS;

use App\Mail\HMS\HostelApplicationAwaitingPaymentMail;
use App\Models\HMS\HostelApplication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendHostelApplicationAwaitingPaymentEmail implements ShouldQueue
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

        $email = $application->student?->user?->email;

        if (blank($email)) {
            return;
        }

        $name = $application->student?->user?->full_name ?? $application->name ?? 'Student';

        Mail::to($email)->send(new HostelApplicationAwaitingPaymentMail(
            $name,
            route('portal.dashboard', absolute: true),
        ));
    }
}
