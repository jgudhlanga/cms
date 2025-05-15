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
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 * @method static filter(DepartmentMetaDataFilter $filters)
 */
class DepartmentCourse extends Model
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = [
        'tenant_id', 'institution_department_id', 'course_id',
        'description', 'show_on_current_application_period', 'course_duration'
    ];


    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class, 'institution_department_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('DepartmentCourse')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
