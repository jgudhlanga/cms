<?php

namespace App\Models\Institution;

use App\Models\Rbac\Role;
use App\Models\Shared\WorkflowStepAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class DepartmentWorkflowStep extends Model
{
    use  SoftDeletes, LogsActivity;

    protected $fillable = [
        'steppable_id',
        'steppable_type',
        'role_ids',
        'staff_ids',
        'workflow_action_ids',
        'notes',
    ];

    public function steppable(): MorphTo
    {
        return $this->morphTo();
    }

    protected $casts = [
        'role_ids' => 'array',
        'staff_ids' => 'array',
        'workflow_action_ids' => 'array',
    ];

    protected $appends = ['role_ids', 'staff_ids', 'workflow_action_ids',];

    public function getRolesAttribute(): Collection
    {
        return Role::whereIn('id', $this->role_ids)->get();
    }

    public function getStaffAttribute(): Collection
    {
        return Staff::whereIn('id', $this->staff_ids)->get();
    }

    public function getWorkflowActionAttribute(): Collection
    {
        return WorkflowStepAction::whereIn('id', $this->workflow_action_ids)->get();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('DepartmentWorkflowStep')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
