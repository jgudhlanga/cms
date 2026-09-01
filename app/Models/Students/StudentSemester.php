<?php

declare(strict_types=1);

namespace App\Models\Students;

use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\ProgrammeSemester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentSemester extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'student_enrolment_id',
        'semester_id',
        'programme_semester_id',
        'student_enrolment_status_id',
        'course_syllabus_ids',
    ];

    protected $casts = [
        'course_syllabus_ids' => 'array',
    ];

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrolment::class, 'student_enrolment_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function programmeSemester(): BelongsTo
    {
        return $this->belongsTo(ProgrammeSemester::class, 'programme_semester_id');
    }

    public function studentEnrolmentStatus(): BelongsTo
    {
        return $this->belongsTo(StudentEnrolmentStatus::class, 'student_enrolment_status_id');
    }

    public function academicCalendarStudentEnrolments(): HasMany
    {
        return $this->hasMany(AcademicCalendarStudentEnrolment::class, 'student_semesters_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentSemester')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
