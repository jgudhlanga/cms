<?php

namespace App\Models\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Traits\AssignsPosition;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HigherOrderCollectionProxy;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @method static filter(SharedNameFilter $filters)
 *
 * @property HigherOrderCollectionProxy|mixed|null $has_application_fee_payment
 */
class Level extends Model
{
    use AssignsPosition, Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = ['name', 'position', 'description',
        'allowed_applications_per_level', 'show_on_current_application_period', 'has_application_fee_payment', 'calendar_type'];

    protected function casts(): array
    {
        return [
            'calendar_type' => AcademicCalendarTypeEnum::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Level')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
