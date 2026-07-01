<?php

declare(strict_types=1);

namespace App\Models\Enrolments;

use App\Enums\Enrolments\BulkFinaliseEnrolmentAuditEventEnum;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Builder
 */
class BulkFinaliseEnrolmentAuditLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'run_id',
        'event',
        'user_id',
        'student_application_id',
        'payment_eligibility',
        'force_finalise',
        'reason',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event' => BulkFinaliseEnrolmentAuditEventEnum::class,
            'force_finalise' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentApplication(): BelongsTo
    {
        return $this->belongsTo(StudentApplication::class);
    }
}
