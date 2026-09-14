<?php

namespace App\Models\AcademicCalendars;

use App\Models\Institution\Staff;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ClassConfigLecturerInCharge extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'class_config_lecturers_in_charge';

    protected $fillable = [
        'tenant_id',
        'class_config_id',
        'staff_id',
        'assigned_by',
    ];

    public function classConfig(): BelongsTo
    {
        return $this->belongsTo(ClassConfig::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ClassConfigLecturerInCharge')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
