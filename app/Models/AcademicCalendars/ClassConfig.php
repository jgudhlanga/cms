<?php

namespace App\Models\AcademicCalendars;

use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class ClassConfig extends Model
{
    use SoftDeletes, Paginatable, LogsActivity;

    protected $fillable = ['academic_calendar_id', 'institution_department_id', 'department_course_id', 'department_level_id', 'students_per_class'];


    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class);
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class);
    }

    public function departmentCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentCourse::class);
    }

    public function departmentLevel(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevel::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ClassConfig')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
