<?php

namespace App\Models\HMS;

use App\Events\HMS\HostelRoomOccupancySynced;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class HostelRoom extends Model
{
    use BelongsToTenant, Filterable, LogsActivity,Paginatable, SoftDeletes;

    protected $fillable = [
        'name',
        'hostel_id',
        'room_type',
        'capacity',
        'status',
        'tenant_id',
        'max_occupancy',
        'current_occupancy',
        'floor_number',
        'description',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(HostelRoomAllocation::class);
    }

    public function syncOccupancyFromAllocations(): void
    {
        $previousOccupancy = (int) $this->current_occupancy;
        $count = $this->allocations()->active()->count();
        $max = max(0, (int) $this->max_occupancy);

        $this->current_occupancy = min($count, $max);

        if ($this->status !== 'maintenance') {
            $this->status = $count === 0 ? 'vacant' : 'occupied';
        }

        $this->saveQuietly();

        HostelRoomOccupancySynced::dispatch($this, $previousOccupancy, (int) $this->current_occupancy);
    }

    public function occupancyCount(): int
    {
        $max = max(0, (int) $this->max_occupancy);

        return min(max(0, (int) $this->current_occupancy), $max);
    }

    public function occupancyLabel(): string
    {
        $max = max(0, (int) $this->max_occupancy);

        return sprintf('%d/%d', $this->occupancyCount(), $max);
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(HostelAmenity::class, 'hostel_room_amenity');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('HostelRoom')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
