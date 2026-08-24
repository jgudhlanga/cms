<?php

declare(strict_types=1);

namespace App\Models\Applications;

use App\Models\Institution\DepartmentCourse;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicationOfferingCourse extends Model
{
    use BelongsToTenant, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'application_offering_level_id',
        'department_course_id',
    ];

    public function offeringLevel(): BelongsTo
    {
        return $this->belongsTo(ApplicationOfferingLevel::class, 'application_offering_level_id');
    }

    public function departmentCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentCourse::class);
    }

    public function modes(): HasMany
    {
        return $this->hasMany(ApplicationOfferingMode::class, 'application_offering_course_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ApplicationOfferingCourse')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
