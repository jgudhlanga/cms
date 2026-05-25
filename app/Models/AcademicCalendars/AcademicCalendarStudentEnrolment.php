<?php

namespace App\Models\AcademicCalendars;

use App\Models\Students\StudentEnrolment;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class AcademicCalendarStudentEnrolment extends Model
{
    use BelongsToTenant, Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    protected $table = 'academic_calendar_student_enrolments';

    protected $fillable = ['tenant_id', 'student_enrolment_id', 'academic_calendar_class_id'];

    public function academicCalendarClass(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendarClass::class, 'academic_calendar_class_id');
    }

    public function studentEnrolment(): BelongsTo 
    {
        return $this->belongsTo(StudentEnrolment::class, 'student_enrolment_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults() 
            ->logFillable()
            ->useLogName('AcademicCalendarStudentEnrolment')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
