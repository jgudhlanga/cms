<?php

namespace App\Models\Institution;

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @method static filter(SharedNameFilter $filters)
 */
class IntakePeriod extends Model
{
    use BelongsToTenant, Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'calendar_year',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'status',
        'is_continuous',
    ];

    protected function casts(): array
    {
        return [
            'status' => IntakePeriodStatusEnum::class,
            'is_continuous' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Integer calendar year for apprentice records (student_apprentices.calendar_year).
     */
    public function calendarYearInteger(): int
    {
        if (is_numeric($this->calendar_year)) {
            return (int) $this->calendar_year;
        }

        if (is_string($this->calendar_year) && preg_match('/(\d{4})/', $this->calendar_year, $matches) === 1) {
            return (int) $matches[1];
        }

        return (int) date('Y', strtotime((string) $this->start_date) ?: time());
    }

    public function scopeContinuous($query)
    {
        return $query->where('is_continuous', true);
    }

    public function scopeRegular($query)
    {
        return $query->where('is_continuous', false);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('IntakePeriod')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
