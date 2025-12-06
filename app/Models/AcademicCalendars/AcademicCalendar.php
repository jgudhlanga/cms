<?php

namespace App\Models\AcademicCalendars;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AcademicCalendar extends Model
{
   use HasFactory, SoftDeletes, Filterable, BelongsToTenant,Paginatable, LogsActivity;

   protected $fillable = ['tenant_id', 'name', 'calendar_year','calendar_type', 'opening_date', 'closing_date', 'description'];

   	public function getActivitylogOptions(): LogOptions
   	{
   		return LogOptions::defaults()
   			->logFillable()
   			->useLogName('AcademicCalendar')
   			->logOnlyDirty()
   			->dontSubmitEmptyLogs();
   	}
}
