<?php

namespace App\Models\Students;

use App\Models\Users\User;
use App\Observers\Students\StudentNoteObserver;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([StudentNoteObserver::class])]
class StudentNote extends Model
{
    use Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = ['noteable_id', 'noteable_type', 'title', 'body', 'created_by', 'updated_by'];

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentNote')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
