<?php

namespace App\Models\Institution;

use App\Models\Institution\Syllabus\CourseSyllabus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class DepartmentLevelCourse extends Model
{
    use LogsActivity;

    protected $fillable = ['department_course_id', 'department_level_id'];

    public function departmentLevel(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevel::class, 'department_level_id');
    }

    public function departmentCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentCourse::class, 'department_course_id');
    }

    public function courseSyllabuses(): HasMany
    {
        return $this->hasMany(CourseSyllabus::class, 'department_level_course_id');
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
