<?php

namespace App\Models\Institution;

use App\Http\Filters\Institution\StaffFilter;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 * @method static filter(StaffFilter $filters)
 */
class Staff extends Model
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $table = 'staff';
    protected $fillable = [
        'tenant_id',
        'user_id',
        'employment_type_id',
        'start_date',
        'end_date',
        'status_id',
        'employee_number',
        'staff_id_number',
        'title_id',
        'gender_id',
        'marital_status_id',
        'race_id',
        'id_type',
        'id_number',
        'passport_number',
        'work_permit_number',
        'country_id',
        'date_of_birth',
        'religion_id',
        'denomination',
        'height',
        'weight',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institutionDepartments(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionDepartment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Staff')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
