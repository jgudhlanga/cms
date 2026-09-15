<?php

namespace App\Models\Institution\AssessmentCalendar;

use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A department's own capture window for a global assessment calendar. Its dates always sit inside
 * the global window; when a department has none, the global window applies.
 *
 * @mixin Builder
 */
class DepartmentAssessmentCalendar extends Model
{
    use BelongsToTenant, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_calendar_id',
        'institution_department_id',
        'start_date',
        'end_date',
        'first_notification_days_before',
        'second_notification_days_before',
        'due_notification_days_before',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'first_notification_days_before' => 'integer',
            'second_notification_days_before' => 'integer',
            'due_notification_days_before' => 'integer',
        ];
    }

    public function assessmentCalendar(): BelongsTo
    {
        return $this->belongsTo(AssessmentCalendar::class);
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Department reminder offsets fall back to the global calendar's offsets when not set.
     */
    public function daysBeforeFor(MissingMarksNotificationTierEnum $tier): int
    {
        $value = match ($tier) {
            MissingMarksNotificationTierEnum::First => $this->first_notification_days_before,
            MissingMarksNotificationTierEnum::Second => $this->second_notification_days_before,
            MissingMarksNotificationTierEnum::Due => $this->due_notification_days_before,
        };

        if ($value !== null) {
            return (int) $value;
        }

        $globalCalendar = $this->assessmentCalendar;

        return $globalCalendar instanceof AssessmentCalendar
            ? $globalCalendar->daysBeforeFor($tier)
            : (new AssessmentCalendar)->daysBeforeFor($tier);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('DepartmentAssessmentCalendar')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
