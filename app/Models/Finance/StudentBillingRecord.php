<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Enums\Finance\StudentBillingStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use App\Traits\Paginatable;
use Database\Factories\Finance\StudentBillingRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBillingRecord extends Model
{
    use BelongsToTenant, HasFactory, Paginatable;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'student_enrolment_id',
        'academic_calendar_id',
        'programme_semester_id',
        'semester_id',
        'student_study_position_confirmation_id',
        'student_billing_batch_id',
        'student_number',
        'status',
        'exported_at',
        'billed_at',
        'billed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentBillingStatusEnum::class,
            'exported_at' => 'datetime',
            'billed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): StudentBillingRecordFactory
    {
        return StudentBillingRecordFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrolment::class, 'student_enrolment_id');
    }

    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class, 'academic_calendar_id');
    }

    public function programmeSemester(): BelongsTo
    {
        return $this->belongsTo(ProgrammeSemester::class, 'programme_semester_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function confirmation(): BelongsTo
    {
        return $this->belongsTo(StudentStudyPositionConfirmation::class, 'student_study_position_confirmation_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StudentBillingBatch::class, 'student_billing_batch_id');
    }

    public function billedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'billed_by');
    }

    public function pastelLink(): BelongsTo
    {
        return $this->belongsTo(PastelLinkedStudent::class, 'student_id', 'student_id');
    }
}
