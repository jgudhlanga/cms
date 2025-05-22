<?php

namespace App\Models\Institution;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class DepartmentLevelRequirement extends Model
{
    use SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = [
        'department_level_id', 'is_o_level_required', 'required_subjects_count', 'main_subjects_count', 'main_subject_ids',
        'other_subjects_count', 'only_read_write_required', 'is_previous_level_required', 'previous_level_id',
    ];

    protected $casts = [
        'main_subject_ids' => 'array', // 👈 Important
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('DepartmentLevelRequirement')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
