<?php

namespace App\Models\Tools;

use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Maintenance extends Model
{
   use SoftDeletes, Filterable,Paginatable, LogsActivity;

   protected $fillable = ['feature', 'is_down', 'url'];

   	public function getActivitylogOptions(): LogOptions
   	{
   		return LogOptions::defaults()
   			->logFillable()
   			->useLogName('Maintenance')
   			->logOnlyDirty()
   			->dontSubmitEmptyLogs();
   	}
}
