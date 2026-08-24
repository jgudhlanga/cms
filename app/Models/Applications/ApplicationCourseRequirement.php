<?php

declare(strict_types=1);

namespace App\Models\Applications;

use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\Subject;
use App\Traits\BelongsToTenant;
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
class ApplicationCourseRequirement extends Model
{
    use BelongsToTenant, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'department_level_id',
        'department_course_id',
        'is_o_level_required',
        'required_subjects_count',
        'main_subjects_count',
        'main_subject_ids',
        'other_subjects_count',
        'only_read_write_required',
        'required_level_id',
    ];

    protected function casts(): array
    {
        return [
            'is_o_level_required' => 'boolean',
            'only_read_write_required' => 'boolean',
            'main_subject_ids' => 'array',
        ];
    }

    protected $appends = ['main_subjects'];

    public function getMainSubjectsAttribute(): Collection
    {
        return Subject::query()->whereIn('id', $this->main_subject_ids ?? [])->get();
    }

    public function departmentLevel(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevel::class);
    }

    public function departmentCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentCourse::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ApplicationCourseRequirement')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
