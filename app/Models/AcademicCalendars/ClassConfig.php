<?php

namespace App\Models\AcademicCalendars;

use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Institution\Syllabus\CourseSyllabus;

/**
 * @mixin Builder
 */
class ClassConfig extends Model
{
    use LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = ['calendar_year', 'academic_year_option_id',
        'institution_department_id', 'department_course_id',
        'department_level_id', 'mode_of_study_id',
        'students_per_class', 'status', 'course_syllabus_ids'];
    protected $casts = [
        'course_syllabus_ids' => 'array',
    ];

     protected $appends = ['syllabus'];

    public function getSyllabusAttribute(): Collection
    {
        return CourseSyllabus::whereIn('id', $this->course_syllabus_ids ?? [])->get();
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

    public function academicYearOption(): BelongsTo
    {
        return $this->belongsTo(AcademicYearOption::class);
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
