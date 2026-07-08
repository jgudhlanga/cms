<?php

namespace App\Models\HMS;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class HostelRoomSection extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'hostel_room_id',
        'name',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'hostel_room_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HostelRoomAllocation::class, 'hostel_room_section_id');
    }

    public function amenities(): MorphToMany
    {
        return $this->morphToMany(HostelAmenity::class, 'amenityable');
    }
}
