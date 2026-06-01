<?php

namespace App\Models\HMS;

use App\Enums\HMS\HostelNoticeStatusEnum;
use App\Enums\HMS\HostelNoticeTypeEnum;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Observers\HMS\HostelNoticeObserver;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([HostelNoticeObserver::class])]
class HostelNotice extends Model
{
    use BelongsToTenant, Filterable, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'posted_by',
        'title',
        'content',
        'type',
        'status',
        'is_urgent',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => HostelNoticeTypeEnum::class,
            'status' => HostelNoticeStatusEnum::class,
            'is_urgent' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function hostels(): BelongsToMany
    {
        return $this->belongsToMany(Hostel::class, 'hostel_notice_hostel');
    }

    public function noticeFloors(): HasMany
    {
        return $this->hasMany(HostelNoticeFloor::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'hostel_notice_student');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('HostelNotice')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
