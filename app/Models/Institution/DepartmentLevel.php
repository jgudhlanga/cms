<?php

namespace App\Models\Institution;

use App\Http\Filters\Institution\DepartmentMetaDataFilter;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 * @method static filter(DepartmentMetaDataFilter $filters)
 */
class DepartmentLevel extends Model
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = ['tenant_id', 'institution_department_id', 'level_id', 'description'];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class, 'institution_department_id');
    }

    public function requirement(): HasOne
    {
        return $this->hasOne(DepartmentLevelRequirement::class, 'department_level_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(DepartmentLevelCourse::class, 'department_level_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('DepartmentLevel')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
