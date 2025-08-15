<?php

namespace App\Models\Institution;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class InstitutionDepartmentMetadata extends Model
{
    use SoftDeletes, BelongsToTenant, LogsActivity;

    protected $fillable = [
        'institution_department_id',
        'code',
        'welcome_title',
        'welcome_note',
        'bio',
        'what_we_offer',
        'facilities_resources',
        'location',
        'email',
        'phone_number',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('InstitutionDepartmentMetadata')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
