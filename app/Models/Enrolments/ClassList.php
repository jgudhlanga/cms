<?php

namespace App\Models\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class ClassList extends Model
{
    use SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = ['tenant_id', 'student_program_id', 'attributes', 'type'];

    protected $casts = [
        'attributes' => 'array',
        'type' => ClassListTypeEnum::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ClassList')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
