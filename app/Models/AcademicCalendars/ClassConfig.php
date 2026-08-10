<?php

namespace App\Models\AcademicCalendars;

use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Relations\BelongsToArrayIds;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class ClassConfig extends Model
{
    use LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = ['calendar_year', 'semester_id',
        'institution_department_id', 'department_course_id',
        'department_level_id', 'mode_of_study_id',
        'students_per_class', 'status', 'course_syllabus_ids'];
    protected $casts = [
        'course_syllabus_ids' => 'array',
    ];

    public function syllabus(): BelongsToArrayIds
    {
        return new BelongsToArrayIds(
            CourseSyllabus::query(),
            $this,
            'course_syllabus_ids'
        );
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

    public function modeOfStudy(): BelongsTo
    {
        return $this->belongsTo(ModeOfStudy::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
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
