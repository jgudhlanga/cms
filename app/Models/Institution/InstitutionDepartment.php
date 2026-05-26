<?php

namespace App\Models\Institution;

use App\Http\Filters\Institution\InstitutionDepartmentFilter;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Students\StudentProgram;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @method static filter(InstitutionDepartmentFilter $filters)
 */
class InstitutionDepartment extends Model
{
    use BelongsToTenant, Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = ['tenant_id', 'department_id', 'description', 'department_code'];

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

    public function metadata(): HasOne
    {
        return $this->hasOne(InstitutionDepartmentMetadata::class, 'institution_department_id');
    }

    public function applicationSteps(): HasMany
    {
        return $this->hasMany(DepartmentApplicationStep::class, 'institution_department_id');
    }

    public function intakeClassSizes(): HasMany
    {
        return $this->hasMany(DepartmentIntakeClassSize::class, 'institution_department_id');
    }

    public function courseSyllabuses(): HasMany
    {
        return $this->hasMany(CourseSyllabus::class, 'institution_department_id');
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(StudentProgram::class, 'institution_department_id');
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
