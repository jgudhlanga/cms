<?php

declare(strict_types=1);

namespace App\Models\Students;

use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgrammeStage extends Model
{
    protected $fillable = [
        'student_id',
        'student_application_id',
        'department_level_course_id',
        'programme_stage_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studentApplication(): BelongsTo
    {
        return $this->belongsTo(StudentApplication::class);
    }

    public function departmentLevelCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevelCourse::class, 'department_level_course_id');
    }

    public function programmeStage(): BelongsTo
    {
        return $this->belongsTo(ProgrammeStage::class);
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }
}
