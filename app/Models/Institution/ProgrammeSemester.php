<?php

declare(strict_types=1);

namespace App\Models\Institution;

use App\Enums\Institution\ProgrammeSemesterKindEnum;
use App\Models\Students\StudentSemester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProgrammeSemester extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'department_level_course_id',
        'position',
        'name',
        'kind',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'kind' => ProgrammeSemesterKindEnum::class,
        ];
    }

    public function departmentLevelCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevelCourse::class, 'department_level_course_id');
    }

    public function isTaught(): bool
    {
        return $this->kind === ProgrammeSemesterKindEnum::TAUGHT
            || $this->kind === null;
    }

    public function isIndustrialAttachment(): bool
    {
        return $this->kind === ProgrammeSemesterKindEnum::INDUSTRIAL_ATTACHMENT;
    }

    public function studentSemesters(): HasMany
    {
        return $this->hasMany(StudentSemester::class, 'programme_semester_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ProgrammeSemester')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
