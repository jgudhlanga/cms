<?php

namespace App\Models\HMS;

use App\Enums\HMS\HostelLeaveStatusEnum;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class HostelLeave extends Model
{
    use BelongsToTenant, Filterable, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'leave_type',
        'from_date',
        'to_date',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => HostelLeaveStatusEnum::class,
            'from_date' => 'date',
            'to_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('HostelLeave')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
