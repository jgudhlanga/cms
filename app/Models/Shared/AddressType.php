<?php

namespace App\Models\Shared;

use App\Http\Filters\Shared\SharedTitleFilter;
use App\Observers\Shared\TitleSlugObserver;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 * @method static filter(SharedTitleFilter $filters)
 */
#[ObservedBy([TitleSlugObserver::class])]
class AddressType extends Model
{
   use HasFactory, SoftDeletes, Filterable ,Paginatable, LogsActivity;

   protected $fillable = ['title','slug', 'description'];

   	public function getActivitylogOptions(): LogOptions
   	{
   		return LogOptions::defaults()
   			->logFillable()
   			->useLogName('AddressType')
   			->logOnlyDirty()
   			->dontSubmitEmptyLogs();
   	}
}
