<?php

declare(strict_types=1);

namespace App\Models\Institution;

use App\Http\Filters\Shared\SharedNameFilter;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
class OfferLetterTemplate extends Model implements HasMedia
{
    use BelongsToTenant, Filterable, HasFactory, InteractsWithMedia, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'intake_period_id',
        'name',
        'helper_description',
        'mode_of_study_id',
        'course_id',
        'tuition_override',
        'header_line_1',
        'header_line_2',
        'header_address_line_1',
        'header_address_line_2',
        'header_telephone',
        'header_email',
        'header_website',
        'header_logo_1',
        'header_logo_2',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'intake_period_id' => 'integer',
            'mode_of_study_id' => 'integer',
            'course_id' => 'integer',
            'tuition_override' => 'decimal:2',
        ];
    }

    public function intakePeriod(): BelongsTo
    {
        return $this->belongsTo(IntakePeriod::class, 'intake_period_id');
    }

    public function modeOfStudy(): BelongsTo
    {
        return $this->belongsTo(ModeOfStudy::class, 'mode_of_study_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function institutionDepartments(): BelongsToMany
    {
        return $this->belongsToMany(
            InstitutionDepartment::class,
            'offer_letter_template_institution_department',
        )->with('department');
    }

    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(Level::class, 'offer_letter_template_level');
    }

    public function headerLogoOne(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'header_logo_1');
    }

    public function headerLogoTwo(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'header_logo_2');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('OfferLetterTemplate')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo-1')->singleFile();
        $this->addMediaCollection('logo-2')->singleFile();
    }

    /**
     * @return list<int>
     */
    public function departmentIds(): array
    {
        return $this->institutionDepartments->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    /**
     * @return list<int>
     */
    public function levelIds(): array
    {
        return $this->levels->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    public function scopeSummary(): string
    {
        return implode('|', [
            (int) $this->intake_period_id,
            implode(',', $this->departmentIds()) ?: 'n',
            implode(',', $this->levelIds()) ?: 'n',
            $this->isFilled($this->course_id) ? (string) $this->course_id : 'n',
            $this->isFilled($this->mode_of_study_id) ? (string) $this->mode_of_study_id : 'n',
        ]);
    }

    private function isFilled(mixed $id): bool
    {
        return $id !== null && (int) $id > 0;
    }
}
