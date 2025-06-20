<?php

namespace App\Models\Students;

use App\Http\Filters\Students\StudentProgramFilter;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 * @method static filter(StudentProgramFilter $filters)
 */
class StudentProgram extends Model
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'department_id',
        'level_id',
        'course_id',
        'o_level_subjects',
        'required_level_completed',
        'read_write_acknowledged'
    ];

    protected $casts = [
        'o_level_subjects' => 'array',
    ];

    protected $appends = ['o_level_subjects'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentProgram')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
