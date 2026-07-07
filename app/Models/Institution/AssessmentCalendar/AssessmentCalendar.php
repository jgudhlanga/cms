<?php

namespace App\Models\Institution\AssessmentCalendar;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Filters\Institution\AssessmentCalendarFilter;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\AssessmentType;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Database\Factories\Institution\AssessmentCalendarFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @method static filter(AssessmentCalendarFilter $filters)
 */
class AssessmentCalendar extends Model
{
    use BelongsToTenant, Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'assessment_type_id',
        'academic_calendar_id',
        'start_date',
        'end_date',
        'type',
    ];

    protected static function newFactory(): AssessmentCalendarFactory
    {
        return AssessmentCalendarFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => AcademicCalendarTypeEnum::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function assessmentType(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class);
    }

    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AssessmentCalendar')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
