<?php

namespace App\Models\Students;

use App\Http\Filters\Students\StudentAcademicResultFilter;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Institution\{Subject, Grade};
use App\Models\Shared\AcademicLevel;

/**
 *
 * @mixin Builder
 * @method static filter(StudentAcademicResultFilter $filters)
 */
class StudentAcademicResult extends Model
{
    use SoftDeletes, Filterable, Paginatable, LogsActivity;

    protected $fillable = ['student_id', 'academic_level_id', 'subject_id', 'exam_year', 'exam_sitting', 'grade_id', 'remarks'];

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'academic_level_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentAcademicResult')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
