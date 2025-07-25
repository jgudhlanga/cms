<?php

namespace App\Models\Institution;

use App\Traits\BelongsToTenant;
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
class DepartmentIntakeClassSize extends Model
{
    use SoftDeletes, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'institution_department_id',
        'department_course_id',
        'department_level_id',
        'class_size',
        'intake_period_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('DepartmentIntakeClassSize')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
