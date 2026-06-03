<?php

namespace App\Models\HMS;

use App\Enums\HMS\HostelQueryCategoryEnum;
use App\Enums\HMS\HostelQueryPriorityEnum;
use App\Enums\HMS\HostelQueryStatusEnum;
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

class HostelQuery extends Model
{
    use BelongsToTenant, Filterable, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'category',
        'subject',
        'description',
        'priority',
        'status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => HostelQueryCategoryEnum::class,
            'priority' => HostelQueryPriorityEnum::class,
            'status' => HostelQueryStatusEnum::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('HostelQuery')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
