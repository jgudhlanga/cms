<?php

declare(strict_types=1);

namespace App\Models\Applications;

use App\Models\Institution\ModeOfStudy;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicationOfferingMode extends Model
{
    use BelongsToTenant, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'application_offering_course_id',
        'mode_of_study_id',
    ];

    public function offeringCourse(): BelongsTo
    {
        return $this->belongsTo(ApplicationOfferingCourse::class, 'application_offering_course_id');
    }

    public function modeOfStudy(): BelongsTo
    {
        return $this->belongsTo(ModeOfStudy::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ApplicationOfferingMode')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
