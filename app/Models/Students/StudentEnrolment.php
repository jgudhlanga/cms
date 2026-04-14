<?php

namespace App\Models\Students;

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @method static filter(Filter $filters)
 */
class StudentEnrolment extends Model
{
    use Filterable, LogsActivity,Paginatable, SoftDeletes;

    protected $fillable = [
        'student_id',
        'institution_department_id',
        'department_level_id',
        'department_course_id',
        'academic_year_option_id',
        'academic_calendar_id',
        'student_enrolment_status_id',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicYearOption(): BelongsTo
    {
        return $this->belongsTo(AcademicYearOption::class, 'academic_year_option_id');
    }

    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class, 'academic_calendar_id');
    }

    public function studentEnrolmentStatus(): BelongsTo
    {
        return $this->belongsTo(StudentEnrolmentStatus::class, 'student_enrolment_status_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentEnrolment')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
