<?php

namespace App\Jobs\Enrolments;

use App\Actions\Documents\GenerateAndStoreOfferLetterAction;
use App\Mail\Enrolments\VerifiedStudentsOfferLetterMail;
use App\Models\Students\StudentApplication;
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

    public function handle(GenerateAndStoreOfferLetterAction $generateAndStoreOfferLetterAction): void
    {
        $application = StudentApplication::query()->findOrFail($this->applicationId);
        $generateAndStoreOfferLetterAction->execute($application, true);

        Mail::to($this->email)->send(new VerifiedStudentsOfferLetterMail($this->name, $this->applicationId));
    }
}
