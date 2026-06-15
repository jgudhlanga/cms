<?php

namespace App\Models\AcademicCalendars;

use App\Enums\AcademicCalendars\CourseWorkAuditEventEnum;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Builder
 */
class CourseWorkAuditLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'course_work_mark_id',
        'event',
        'user_id',
        'student_enrolment_id',
        'course_syllabus_module_id',
        'assessment_type_id',
        'old_values',
        'new_values',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event' => CourseWorkAuditEventEnum::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function courseWorkMark(): BelongsTo
    {
        return $this->belongsTo(CourseWorkMark::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentEnrolment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrolment::class);
    }

    public function courseSyllabusModule(): BelongsTo
    {
        return $this->belongsTo(CourseSyllabusModule::class);
    }

    public function assessmentType(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class);
    }
}
