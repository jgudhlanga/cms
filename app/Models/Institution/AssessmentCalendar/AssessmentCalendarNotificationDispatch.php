<?php

namespace App\Models\Institution\AssessmentCalendar;

use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentCalendarNotificationDispatch extends Model
{
    use BelongsToTenant;

    public const string SCOPE_GLOBAL = 'global';

    protected $fillable = [
        'tenant_id',
        'assessment_calendar_id',
        'institution_department_id',
        'scope_key',
        'tier',
        'sent_at',
    ];

    public static function departmentScope(int $institutionDepartmentId): string
    {
        return 'dept:'.$institutionDepartmentId;
    }

    protected function casts(): array
    {
        return [
            'tier' => MissingMarksNotificationTierEnum::class,
            'sent_at' => 'datetime',
        ];
    }

    public function assessmentCalendar(): BelongsTo
    {
        return $this->belongsTo(AssessmentCalendar::class);
    }
}
