<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Enums\Finance\StudentBillingStatusEnum;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Database\Factories\Finance\StudentBillingBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentBillingBatch extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'reference',
        'filters',
        'row_count',
        'status',
        'exported_by',
        'exported_at',
        'billed_by',
        'billed_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'status' => StudentBillingStatusEnum::class,
            'exported_at' => 'datetime',
            'billed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): StudentBillingBatchFactory
    {
        return StudentBillingBatchFactory::new();
    }

    public function records(): HasMany
    {
        return $this->hasMany(StudentBillingRecord::class, 'student_billing_batch_id');
    }

    public function exportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
    }

    public function billedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'billed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentBillingBatch')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
