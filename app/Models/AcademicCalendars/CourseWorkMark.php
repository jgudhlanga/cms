<?php

namespace App\Models\AcademicCalendars;

use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use App\Traits\Paginatable;
use Database\Factories\AcademicCalendars\CourseWorkMarkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin Builder
 */
class CourseWorkMark extends Model
{
    /** @use HasFactory<CourseWorkMarkFactory> */
    use BelongsToTenant, HasFactory, Paginatable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'student_enrolment_id',
        'course_syllabus_module_id',
        'assessment_type_id',
        'mark',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'mark' => 'integer',
        ];
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
