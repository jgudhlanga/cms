<?php

namespace App\Models\Students;

use App\Http\Filters\Students\StudentProgramFilter;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Observers\Students\StudentProgramObserver;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
 * @method static filter(StudentProgramFilter $filters)
 */
#[ObservedBy([StudentProgramObserver::class])]
class StudentProgram extends Model
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'institution_department_id',
        'department_level_id',
        'department_course_id',
        'o_level_subjects',
        'required_level_completed',
        'read_write_acknowledged',
        'application_tracking_number',
        'department_application_step_id',
        'program_status_id',
    ];

    protected $casts = [
        'o_level_subjects' => 'array',
    ];

    protected $appends = ['o_level_subjects'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class, 'institution_department_id');
    }

    public function departmentLevel(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevel::class, 'department_level_id');
    }

    public function departmentCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentCourse::class, 'department_course_id');
    }

    public function departmentWorkflowStep(): BelongsTo
    {
        return $this->belongsTo(DepartmentApplicationStep::class, 'department_application_step_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentProgram')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
