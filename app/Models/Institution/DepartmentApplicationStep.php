<?php

namespace App\Models\Institution;

use App\Models\Shared\WorkflowStep;
use App\Traits\AssignsPosition;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class DepartmentApplicationStep extends Model
{
    use  SoftDeletes, BelongsToTenant, LogsActivity, AssignsPosition;

    protected $fillable = ['tenant_id', 'institution_department_id', 'workflow_step_id', 'position'];

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }
    public function addresses(): MorphMany
    {
        return $this->morphMany(DepartmentWorkflowStep::class, 'steppable')->withTrashed();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('DepartmentApplicationStep')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
