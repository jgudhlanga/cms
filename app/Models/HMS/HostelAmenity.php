<?php

namespace App\Models\HMS;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HostelAmenity extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
    ];

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(HostelRoom::class, 'hostel_room_amenity');
    }
}
