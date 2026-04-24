<?php

namespace App\Models\Institution;

use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Syllabus\SyllabusCourseModule;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin Builder
 *
 * @method static filter(SharedNameFilter $filters)
 */
class CourseSyllabus extends Model implements HasMedia
{
    use BelongsToTenant, Filterable, HasFactory, InteractsWithMedia, LogsActivity, Paginatable, SoftDeletes;

    public const MEDIA_COLLECTION_SYLLABUS_DOCUMENT = 'syllabus-document';

    protected $table = 'course_syllabuses';

    protected $fillable = [
        'tenant_id',
        'institution_department_id',
        'department_level_course_id',
        'title',
        'code',
        'implementation_year',
        'syllabus_document_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseSyllabusStatusEnum::class,
        ];
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class, 'institution_department_id');
    }

    public function departmentLevelCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevelCourse::class, 'department_level_course_id');
    }

    public function syllabusDocument(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'syllabus_document_id');
    }

    public function syllabusCourseModules(): HasMany
    {
        return $this->hasMany(SyllabusCourseModule::class, 'course_syllabus_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_SYLLABUS_DOCUMENT)->singleFile();
    }

    public function getSyllabusDocumentUrlAttribute(): ?string
    {
        if (! $this->syllabus_document_id || $this->syllabus_document_id <= 0) {
            return null;
        }

        return $this->syllabusDocument?->getFullUrl();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('CourseSyllabus')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
