<?php

namespace App\Models\HMS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelNoticeFloor extends Model
{
    protected $fillable = [
        'hostel_notice_id',
        'hostel_id',
        'floor_number',
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(HostelNotice::class, 'hostel_notice_id');
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }
}
