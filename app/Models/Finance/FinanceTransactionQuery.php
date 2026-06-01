<?php

namespace App\Models\Finance;

use App\Enums\Finance\FinanceTransactionQueryStatusEnum;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @mixin Builder
 */
class FinanceTransactionQuery extends Model implements HasMedia
{
    use Filterable, InteractsWithMedia, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = [
        'student_id',
        'bank_statement_id',
        'payment_reference',
        'description',
        'status',
        'decline_reason',
        'reconciled_by',
        'declined_by',
        'reconciled_at',
        'declined_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => FinanceTransactionQueryStatusEnum::class,
            'reconciled_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(ZBBankStatement::class, 'bank_statement_id');
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function decliner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declined_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('financial-documents')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('FinanceTransactionQuery')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
