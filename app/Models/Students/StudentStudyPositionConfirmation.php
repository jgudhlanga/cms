<?php

declare(strict_types=1);

namespace App\Models\Students;

use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionStateEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A student's confirmed programme phase for one enrolment in one calendar period.
 *
 * @property StudyPositionAnswerEnum $answer
 * @property StudyPositionSourceEnum $source
 * @property StudyPositionSyncStatusEnum $sync_status
 *
 * @mixin Builder
 */
class StudentStudyPositionConfirmation extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'student_enrolment_id',
        'academic_calendar_id',
        'semester_id',
        'programme_semester_id',
        'previous_programme_semester_id',
        'student_semester_id',
        'answer',
        'source',
        'sync_status',
        'sync_note',
        'reason',
        'evidence',
        'confirmed_by',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'answer' => StudyPositionAnswerEnum::class,
            'source' => StudyPositionSourceEnum::class,
            'sync_status' => StudyPositionSyncStatusEnum::class,
            'evidence' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrolment::class, 'student_enrolment_id');
    }

    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class, 'academic_calendar_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function programmeSemester(): BelongsTo
    {
        return $this->belongsTo(ProgrammeSemester::class, 'programme_semester_id');
    }

    public function previousProgrammeSemester(): BelongsTo
    {
        return $this->belongsTo(ProgrammeSemester::class, 'previous_programme_semester_id');
    }

    public function studentSemester(): BelongsTo
    {
        return $this->belongsTo(StudentSemester::class, 'student_semester_id');
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * @param  Builder<StudentStudyPositionConfirmation>  $query
     * @param  list<int>  $periodIds
     * @return Builder<StudentStudyPositionConfirmation>
     */
    public function scopeForPeriods(Builder $query, array $periodIds): Builder
    {
        return $query->whereIn('academic_calendar_id', $periodIds === [] ? [0] : $periodIds);
    }

    public function state(): StudyPositionStateEnum
    {
        return StudyPositionStateEnum::fromConfirmation($this);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentStudyPositionConfirmation')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
