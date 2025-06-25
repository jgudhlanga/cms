<?php

namespace App\Models\Students;

use App\Http\Filters\Students\StudentFilter;
use App\Models\Shared\Address;
use App\Models\Shared\Contact;
use App\Models\Shared\Country;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\NextOfKin;
use App\Models\Shared\Religion;
use App\Models\Shared\Title;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        'race_id',
        'id_type',
        'id_number',
        'passport_number',
        'country_id',
        'study_permit_number',
        'date_of_birth',
        'religion_id',
        'denomination',
        'height',
        'weight',
    ];

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function programmes(): HasMany
    {
        return $this->hasMany(StudentProgram::class, 'student_id')->withTrashed();
    }

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable')->withTrashed();
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable')->withTrashed();
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }


    public function nextOfKins(): MorphMany
    {
        return $this->morphMany(NextOfKin::class, 'kinnable')->withTrashed();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Student')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
