<?php

declare(strict_types=1);

namespace App\Models\Students;

use App\Enums\Students\ModuleExemptionSourceEnum;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentModuleExemption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_application_id',
        'course_syllabus_module_id',
        'source',
        'evidence_reference',
        'granted_by',
        'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => ModuleExemptionSourceEnum::class,
            'granted_at' => 'datetime',
        ];
    }

    public function studentApplication(): BelongsTo
    {
        return $this->belongsTo(StudentApplication::class, 'student_application_id');
    }

    public function courseSyllabusModule(): BelongsTo
    {
        return $this->belongsTo(CourseSyllabusModule::class, 'course_syllabus_module_id');
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
