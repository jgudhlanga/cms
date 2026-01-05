<?php

namespace App\Models\Students;

use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentNote extends Model
{
    use HasFactory, SoftDeletes, Filterable, Paginatable, LogsActivity;

    protected $fillable = ['noteable_id', 'noteable_type', 'title', 'body'];


    public function noteable(): MorphTo
    {
        return $this->morphTo();
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
