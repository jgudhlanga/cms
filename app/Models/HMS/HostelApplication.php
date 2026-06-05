<?php

namespace App\Models\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Models\Shared\Gender;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Observers\HMS\HostelApplicationObserver;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([HostelApplicationObserver::class])]
class HostelApplication extends Model
{
    use BelongsToTenant, Filterable, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'student_enrolment_id',
        'name',
        'gender_id',
        'type',
        'status',
        'phone_number',
        'email_address',
        'next_of_kin_name',
        'next_of_kin_contact',
        'check_in',
        'check_out',
        'eligibility_results',
        'address_outside_campus_priority',
        'payment_verification',
        'decline_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => HostelApplicationTypeEnum::class,
            'status' => HostelApplicationStatusEnum::class,
            'check_in' => 'date',
            'check_out' => 'date',
            'eligibility_results' => 'array',
            'address_outside_campus_priority' => 'boolean',
            'payment_verification' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studentEnrolment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrolment::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('HostelApplication')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
