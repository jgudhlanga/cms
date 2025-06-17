<?php

namespace App\Models\Institution;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'other_subjects_count', 'only_read_write_required', 'required_level_id',
    ];

    protected $casts = [
        'main_subject_ids' => 'array', // 👈 Important
    ];

    protected $appends = ['main_subjects'];
    public function getMainSubjectsAttribute(): Collection
    {
        return Subject::whereIn('id', $this->main_subject_ids)->get();
    }

    public function requiredLevel(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevel::class, 'required_level_id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('DepartmentLevelRequirement')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
