<?php

namespace App\Models\Preferences;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserPreference extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'side_bar_state',
    ];

    protected function casts(): array
    {
        return [
            'side_bar_state' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('UserPreference')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
