<?php

namespace App\Models\Students;

use App\Http\Filters\Students\StudentFilter;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 * @method static filter(StudentFilter $filters)
 */
class Student extends Model
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'title_id',
        'gender_id',
        'marital_status_id',
        'title_id',
        'race_id',
        'id_type',
        'id_number',
        'passport_number',
        'country_id',
        'study_permit_number',
        'date_of_birth',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Student')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
