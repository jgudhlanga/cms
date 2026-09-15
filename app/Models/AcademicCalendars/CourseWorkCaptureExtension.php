<?php

namespace App\Models\AcademicCalendars;

use App\Enums\AcademicCalendars\CourseWorkExtensionStatusEnum;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A time-boxed reopening of coursework capture for one class, module and assessment type
 * (assessment type is null for mark-only modules) after its window has closed.
 *
 * @mixin Builder
 */
class CourseWorkCaptureExtension extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'academic_calendar_class_id',
        'course_syllabus_module_id',
        'assessment_type_id',
        'institution_department_id',
        'assessment_calendar_id',
        'requested_by',
        'reason',
        'requested_until',
        'status',
        'decided_by',
        'approved_until',
        'decision_note',
        'decided_at',
        'revoked_by',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseWorkExtensionStatusEnum::class,
            'requested_until' => 'date',
            'approved_until' => 'date',
            'decided_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function academicCalendarClass(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendarClass::class);
    }

    public function courseSyllabusModule(): BelongsTo
    {
        return $this->belongsTo(CourseSyllabusModule::class);
    }

    public function assessmentType(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class);
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class);
    }

    public function assessmentCalendar(): BelongsTo
    {
        return $this->belongsTo(AssessmentCalendar::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Approved extensions whose reopened window still covers the given day.
     */
    public function scopeActiveOn(Builder $query, CarbonInterface $day): Builder
    {
        return $query
            ->where('status', CourseWorkExtensionStatusEnum::Approved->value)
            ->whereNull('revoked_at')
            ->whereDate('approved_until', '>=', $day->toDateString());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('CourseWorkCaptureExtension')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
