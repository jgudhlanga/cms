<?php

namespace App\Models\Institution;

use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @method static filter(SharedNameFilter $filters)
 */
class AssessmentType extends Model
{
    use BelongsToTenant, Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = ['tenant_id', 'name', 'modes_of_study', 'description', 'weight_percent'];

    protected function casts(): array
    {
        return [
            'modes_of_study' => 'array',
        ];
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(AssessmentCalendar::class);
    }

    public function modeOfStudyNames(): string
    {
        $modeIds = collect($this->modes_of_study)
            ->filter()
            ->map(fn (mixed $modeId): int => (int) $modeId)
            ->all();

        if ($modeIds === []) {
            return '';
        }

        return ModeOfStudy::query()
            ->whereIn('id', $modeIds)
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AssessmentType')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
