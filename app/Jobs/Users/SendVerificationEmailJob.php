<?php

namespace App\Jobs\Users;

use App\Models\Users\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendVerificationEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user)
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->sendEmailVerificationNotification();
    }
}
