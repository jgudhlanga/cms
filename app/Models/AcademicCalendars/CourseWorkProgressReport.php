<?php

namespace App\Models\AcademicCalendars;

use App\Models\Institution\InstitutionDepartment;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CourseWorkProgressReport extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'class_config_id',
        'institution_department_id',
        'academic_calendar_id',
        'submitted_by',
        'snapshot',
        'notes',
        'submitted_at',
        'acknowledged_by',
        'acknowledged_at',
        'hod_comment',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'submitted_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function classConfig(): BelongsTo
    {
        return $this->belongsTo(ClassConfig::class);
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['class_config_id', 'submitted_by', 'notes', 'acknowledged_by', 'acknowledged_at', 'hod_comment'])
            ->useLogName('CourseWorkProgressReport')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
