<?php

namespace App\Models\Institution\Syllabus;

use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Institution\Staff;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @method static filter(SharedTitleFilter $filters)
 */
class CourseSyllabusModule extends Model
{
    use BelongsToTenant, Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    protected $table = 'course_syllabus_modules';

    protected $fillable = [
        'tenant_id',
        'course_syllabus_id',
        'academic_year_option_id',
        'title',
        'code',
        'duration_in_hours',
        'nql_level',
        'prerequisite_module_ids',
        'shared',
        'all_semesters',
        'capture_mark_only',
    ];

    protected function casts(): array
    {
        return [
            'prerequisite_module_ids' => 'array',
            'shared' => 'boolean',
            'all_semesters' => 'boolean',
            'capture_mark_only' => 'boolean',
        ];
    }

    public function courseSyllabus(): BelongsTo
    {
        return $this->belongsTo(CourseSyllabus::class, 'course_syllabus_id');
    }

    public function academicYearOption(): BelongsTo
    {
        return $this->belongsTo(AcademicYearOption::class, 'academic_year_option_id');
    }

    public function lecturers(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'course_syllabus_module_lecturers')
            ->wherePivotNull('academic_calendar_class_id')
            ->withPivot('academic_calendar_class_id', 'tenant_id')
            ->withTimestamps();
    }

    public function classLecturers(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'course_syllabus_module_lecturers')
            ->wherePivotNotNull('academic_calendar_class_id')
            ->withPivot('academic_calendar_class_id', 'tenant_id')
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('CourseSyllabusModule')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
