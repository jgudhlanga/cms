<?php

namespace App\Services\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Jobs\HMS\SendHostelApplicationAwaitingPaymentEmail;
use App\Jobs\HMS\SendHostelApplicationDeclinedEmail;
use App\Models\HMS\HostelApplication;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class HostelApplicationReviewService
{
    public function guardRequestPayment(HostelApplication $application): void
    {
        $previousStatus = $application->getOriginal('status');
        $previousValue = $previousStatus instanceof HostelApplicationStatusEnum
            ? $previousStatus->value
            : (string) $previousStatus;

        if ($previousValue !== HostelApplicationStatusEnum::PENDING->value) {
            throw ValidationException::withMessages([
                'status' => [__('hms.application_not_pending')],
            ]);
        }

        if ($application->type !== HostelApplicationTypeEnum::STUDENT) {
            throw ValidationException::withMessages([
                'status' => [__('hms.guest_awaiting_payment_not_supported')],
            ]);
        }
    }

    public function dispatchAwaitingPaymentEmail(HostelApplication $application): void
    {
        $application->loadMissing('student.user');

        $email = $application->student?->user?->email;

        if (blank($email)) {
            Log::warning('Hostel application awaiting-payment email skipped: missing student email', [
                'hostel_application_id' => $application->id,
            ]);

            return;
        }

        SendHostelApplicationAwaitingPaymentEmail::dispatch($application->id);
    }

    public function dispatchDeclinedEmail(HostelApplication $application): void
    {
        $application->loadMissing('student.user');

        $email = $application->student?->user?->email ?? $application->email_address;

        if (blank($email)) {
            Log::warning('Hostel application declined email skipped: missing recipient email', [
                'hostel_application_id' => $application->id,
            ]);

            return;
        }

        SendHostelApplicationDeclinedEmail::dispatch($application->id);
    }
}
