<?php

namespace App\Models\AcademicCalendars;

use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class AcademicCalendarClass extends Model
{
    use BelongsToTenant, Filterable, LogsActivity,Paginatable, SoftDeletes;

    protected $table = 'academic_calendar_classes';

    protected $fillable = ['tenant_id', 'class_config_id', 'name', 'description'];

    public function classConfig(): BelongsTo
    {
        return $this->belongsTo(ClassConfig::class, 'class_config_id');
    }


    public function studentEnrolments(): HasMany
    {
        return $this->hasMany(AcademicCalendarStudentEnrolment::class, 'academic_calendar_class_id');
    }

    public function metaData(): HasMany
    {
        return $this->hasMany(AcademicCalendarClassMetaData::class, 'academic_calendar_class_id');
    }

    public function lecturerMetaData(): HasOne
    {
        return $this->hasOne(AcademicCalendarClassMetaData::class, 'academic_calendar_class_id')
            ->whereHas('classMetadataType', fn (Builder $query): Builder => $query->where('name', 'lecturer'));
    }

    public function moduleLecturers(): BelongsToMany
    {
        return $this->belongsToMany(CourseSyllabusModule::class, 'course_syllabus_module_lecturers')
            ->wherePivotNotNull('academic_calendar_class_id')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalendarClass')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
