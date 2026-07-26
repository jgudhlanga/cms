<?php

namespace App\Models\Institution;

use App\Http\Filters\Shared\SharedNameFilter;
use App\Traits\AssignsPosition;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @method static filter(SharedNameFilter $filters)
 */
class Division extends Model
{
    use AssignsPosition, Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = ['name', 'position', 'description', 'head_of_division_id'];

    public function headOfDivision(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'head_of_division_id');
    }

    public function institutionDepartments(): HasMany
    {
        return $this->hasMany(InstitutionDepartment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Division')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
