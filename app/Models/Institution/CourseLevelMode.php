<?php

namespace App\Models\Institution;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class CourseLevelMode extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = ['department_course_id', 'department_level_id', 'modes'];

    protected $casts = [
        'modes' => 'array',
    ];

    protected $appends = ['mode_objects'];

    public function getModeObjectsAttribute(): Collection
    {
        return ModeOfStudy::whereIn('id', $this->modes ?? [])->get();
    }

    public function departmentCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentCourse::class);
    }

    public function departmentLevel(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevel::class);
    }

    public function scopeForLinkedLevels(Builder $query): Builder
    {
        return $query->whereExists(function ($subquery): void {
            $subquery->selectRaw('1')
                ->from('department_level_courses as dlc')
                ->whereColumn('dlc.department_course_id', 'course_level_modes.department_course_id')
                ->whereColumn('dlc.department_level_id', 'course_level_modes.department_level_id');
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('CourseLevelMode')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
