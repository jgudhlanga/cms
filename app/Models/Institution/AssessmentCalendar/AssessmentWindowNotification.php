<?php

namespace App\Models\Institution\AssessmentCalendar;

use App\Enums\Assessments\AssessmentWindowEventEnum;
use App\Models\Institution\InstitutionDepartment;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records that a department was told its capture window opened or closed, so each is sent once.
 */
class AssessmentWindowNotification extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'assessment_calendar_id',
        'institution_department_id',
        'event',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'event' => AssessmentWindowEventEnum::class,
            'sent_at' => 'datetime',
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
}
