<?php

declare(strict_types=1);

namespace App\Models\Applications;

use App\Models\Institution\InstitutionDepartment;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicationOfferingDepartment extends Model
{
    use BelongsToTenant, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'institution_department_id',
        'has_apprentice_programmes',
    ];

    protected function casts(): array
    {
        return [
            'has_apprentice_programmes' => 'boolean',
        ];
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(ApplicationOfferingLevel::class, 'application_offering_department_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ApplicationOfferingDepartment')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
