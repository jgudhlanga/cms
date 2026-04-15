<?php

namespace App\Models\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class AcademicCalendar extends Model
{
    use LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = ['calendar_year', 'type', 'opening_date', 'closing_date'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AcademicCalendarTypeEnum::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalendar')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
