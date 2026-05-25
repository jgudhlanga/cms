<?php

namespace App\Models\HMS;

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Models\Students\Student;
use App\Observers\HMS\HostelRoomAllocationObserver;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
#[ObservedBy([HostelRoomAllocationObserver::class])]
class HostelRoomAllocation extends Model
{
    use BelongsToTenant, Filterable, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'hostel_room_id',
        'student_id',
        'type',
        'status',
        'check_in',
        'check_out',
    ];

    protected function casts(): array
    {
        return [
            'type' => HostelAllocationTypeEnum::class,
            'status' => HostelAllocationStatusEnum::class,
            'check_in' => 'date',
            'check_out' => 'date',
        ];
    }

    /**
     * Active allocations: currently occupying a bed (check_out holds planned semester end).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', HostelAllocationStatusEnum::ACTIVE->value);
    }

    /**
     * Allocations that have not been checked out (any status except checked-out).
     */
    public function scopeNotCheckedOut(Builder $query): Builder
    {
        return $query->where('status', '!=', HostelAllocationStatusEnum::CHECKED_OUT->value);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'hostel_room_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isActive(): bool
    {
        return $this->status === HostelAllocationStatusEnum::ACTIVE;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('HostelRoomAllocation')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
