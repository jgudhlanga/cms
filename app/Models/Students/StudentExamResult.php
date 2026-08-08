<?php

declare(strict_types=1);

namespace App\Models\Students;

use App\Enums\Students\StudentExamResultComment;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentExamResult extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'candidate_number',
        'id_number',
        'institution_department_id',
        'department_level_id',
        'department_course_id',
        'mode_of_study_id',
        'calendar_year',
        'session',
        'comment',
        'raw_course_comment',
        'comment_needs_review',
    ];

    protected function casts(): array
    {
        return [
            'calendar_year' => 'integer',
            'comment' => StudentExamResultComment::class,
            'comment_needs_review' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class);
    }

    public function departmentLevel(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevel::class);
    }

    public function departmentCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentCourse::class);
    }

    public function modeOfStudy(): BelongsTo
    {
        return $this->belongsTo(ModeOfStudy::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentExamResult')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
