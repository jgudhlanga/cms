<?php

namespace App\Models\Institution;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class DepartmentCourseLevel extends Model
{
    use  LogsActivity;

    protected $fillable = ['department_course_id', 'department_level_id'];

    public function departmentLevel(): BelongsTo
    {
      return $this->belongsTo(DepartmentLevel::class, 'department_level_id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('DepartmentCourseLevel')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
