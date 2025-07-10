<?php

namespace App\Models\Institution;

use App\Http\Filters\Institution\InstitutionDepartmentFilter;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 * @method static filter(InstitutionDepartmentFilter $filters)
 */
class InstitutionDepartment extends Model
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = ['tenant_id', 'department_id', 'description'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function departmentLevels(): HasMany
    {
        return $this->hasMany(DepartmentLevel::class, 'institution_department_id')->orderBy('level_id');
    }

    public function departmentCourses(): HasMany
    {
        return $this->hasMany(DepartmentCourse::class, 'institution_department_id');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'institution_department_staff');
    }

    public function applicationSteps(): HasMany
    {
        return $this->hasMany(DepartmentApplicationStep::class, 'institution_department_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('InstitutionDepartment')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
