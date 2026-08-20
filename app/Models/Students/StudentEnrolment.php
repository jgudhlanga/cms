<?php

namespace App\Models\Students;

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'student_application_id',
        'institution_department_id',
        'department_level_id',
        'department_course_id',
        'semester_id',
        'academic_calendar_id',
        'mode_of_study_id',
        'student_enrolment_status_id',
        'course_syllabus_ids',
    ];

    protected $casts = [
        'course_syllabus_ids' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function studentApplication(): BelongsTo
    {
        return $this->belongsTo(StudentApplication::class, 'student_application_id');
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class, 'institution_department_id');
    }

    public function departmentLevel(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevel::class, 'department_level_id');
    }

    public function departmentCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentCourse::class, 'department_course_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class, 'academic_calendar_id');
    }

    public function modeOfStudy(): BelongsTo
    {
        return $this->belongsTo(ModeOfStudy::class, 'mode_of_study_id');
    }

    public function studentEnrolmentStatus(): BelongsTo
    {
        return $this->belongsTo(StudentEnrolmentStatus::class, 'student_enrolment_status_id');
    }

    public function academicCalendarStudentEnrolment(): HasOne
    {
        return $this->hasOne(
            AcademicCalendarStudentEnrolment::class,
            'student_enrolment_id'
        );
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
