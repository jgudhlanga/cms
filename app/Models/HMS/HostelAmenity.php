<?php

namespace App\Models\HMS;

use App\Observers\Shared\NameSlugObserver;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([NameSlugObserver::class])]
class HostelAmenity extends Model
{
    use BelongsToTenant, Filterable, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'market_value',
    ];

    protected function casts(): array
    {
        return [
            'market_value' => 'float',
        ];
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(HostelRoom::class, 'hostel_room_amenity');
    }

    public function roomSections(): MorphToMany
    {
        return $this->morphedByMany(HostelRoomSection::class, 'amenityable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('HostelAmenity')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
