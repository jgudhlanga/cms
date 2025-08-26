<?php

namespace App\Models\Institution;

use App\Http\Filters\Shared\SharedNameFilter;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 *
 * @mixin Builder
 * @method static filter(SharedNameFilter $filters)
 */
class DocumentTemplate extends Model implements HasMedia
{
   use HasFactory, SoftDeletes, Filterable, BelongsToTenant,Paginatable, LogsActivity, InteractsWithMedia;


   protected $fillable = ['tenant_id', 'name', 'document_type_id', 'header_line_1', 'header_line_2', 'header_address_line_1',
       'header_address_line_2', 'header_telephone', 'header_email', 'header_website', 'header_logo_1', 'header_logo_2',
       'body'
   ];

   	public function getActivitylogOptions(): LogOptions
   	{
   		return LogOptions::defaults()
   			->logFillable()
   			->useLogName('DocumentTemplate')
   			->logOnlyDirty()
   			->dontSubmitEmptyLogs();
   	}

    public function headerLogoOne(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'header_logo_1');
    }

    public function headerLogoTwo(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'header_logo_2');
    }

    public function getHeaderLogoOneUrlAttribute()
    {
        return ($this->header_logo_1 > 0) ? $this->headerLogoOne->getFullUrl() : null;
    }

    public function getHeaderLogoTwoUrlAttribute()
    {
        return ($this->header_logo_2 > 0) ? $this->headerLogoTwo->getFullUrl() : null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document-templates');
    }
}
