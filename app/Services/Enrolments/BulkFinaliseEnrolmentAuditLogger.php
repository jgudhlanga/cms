<?php

declare(strict_types=1);

namespace App\Services\Enrolments;

use App\Enums\Enrolments\BulkFinaliseEnrolmentAuditEventEnum;
use App\Models\Enrolments\BulkFinaliseEnrolmentAuditLog;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;

class BulkFinaliseEnrolmentAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        string $runId,
        BulkFinaliseEnrolmentAuditEventEnum $event,
        ?int $userId = null,
        ?StudentApplication $studentApplication = null,
        ?string $paymentEligibility = null,
        bool $forceFinalise = false,
        ?string $reason = null,
        ?array $metadata = null,
    ): BulkFinaliseEnrolmentAuditLog {
        $tenantId = $studentApplication?->tenant_id;

        if ($tenantId === null && $userId !== null) {
            $tenantId = User::query()->whereKey($userId)->value('tenant_id');
        }

        if ($tenantId === null) {
            $tenantId = (int) config('app.tenant_id', 1);
        }

        return BulkFinaliseEnrolmentAuditLog::query()->create([
            'tenant_id' => $tenantId,
            'run_id' => $runId,
            'event' => $event,
            'user_id' => $userId,
            'student_application_id' => $studentApplication?->id,
            'payment_eligibility' => $paymentEligibility,
            'force_finalise' => $forceFinalise,
            'reason' => $reason,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
