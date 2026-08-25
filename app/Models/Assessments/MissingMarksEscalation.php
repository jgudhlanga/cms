<?php

namespace App\Models\Assessments;

use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MissingMarksEscalation extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'assessment_calendar_id',
        'escalated_by',
        'notes',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function assessmentCalendar(): BelongsTo
    {
        return $this->belongsTo(AssessmentCalendar::class);
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('MissingMarksEscalation')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
