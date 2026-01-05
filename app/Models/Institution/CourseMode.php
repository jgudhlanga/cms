<?php

namespace App\Models\Institution;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class CourseMode extends Model
{
    use LogsActivity;

    protected $fillable = ['department_course_id', 'mode_of_study_id'];

    public function modeOfStudy(): BelongsTo
    {
        return $this->belongsTo(ModeOfStudy::class, 'mode_of_study_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('CourseMode')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
