<?php

declare(strict_types=1);

namespace App\Models\Applications;

use App\Models\Institution\DepartmentLevel;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicationOfferingLevel extends Model
{
    use BelongsToTenant, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'application_offering_department_id',
        'department_level_id',
    ];

    public function offeringDepartment(): BelongsTo
    {
        return $this->belongsTo(ApplicationOfferingDepartment::class, 'application_offering_department_id');
    }

    public function departmentLevel(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevel::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(ApplicationOfferingCourse::class, 'application_offering_level_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ApplicationOfferingLevel')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
