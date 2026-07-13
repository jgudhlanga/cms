<?php

namespace App\Models\HMS;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class HmsSetting extends Model
{
    use BelongsToTenant, Filterable, LogsActivity, Paginatable;

    protected $table = 'hms_settings';

    protected $fillable = [
        'tenant_id',
        'require_full_time_study',
        'full_time_mode_name',
        'require_tuition_paid',
        'require_accommodation_paid',
        'require_address_outside_campus',
        'campus_city',
        'allow_guests',
        'auto_allocate_rooms',
        'days_to_pay',
        'applications_open',
        'application_start_date',
        'application_end_date',
    ];

    protected function casts(): array
    {
        return [
            'require_full_time_study' => 'boolean',
            'require_tuition_paid' => 'boolean',
            'require_accommodation_paid' => 'boolean',
            'require_address_outside_campus' => 'boolean',
            'allow_guests' => 'boolean',
            'auto_allocate_rooms' => 'boolean',
            'days_to_pay' => 'integer',
            'applications_open' => 'boolean',
            'application_start_date' => 'date',
            'application_end_date' => 'date',
        ];
    }

    public static function resolveForTenant(?int $tenantId = null): self
    {
        $tenantId ??= Auth::user()?->tenant_id;

        return self::query()->firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'require_full_time_study' => true,
                'full_time_mode_name' => 'Full Time',
                'require_tuition_paid' => true,
                'require_accommodation_paid' => true,
                'require_address_outside_campus' => true,
                'campus_city' => 'Harare',
                'allow_guests' => false,
                'auto_allocate_rooms' => false,
                'days_to_pay' => 7,
                'applications_open' => false,
            ],
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('HmsSetting')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
