<?php

namespace App\Models\Students;

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
 */
class AcademicRecord extends Model
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = [
        'tenant_id', 'student_id', 'school', 'place', 'from_level', 'to_level',
        'from_year', 'to_year', 'student_unique_number', 'exam_board', 'exam_month', 'exam_year', 'exam_center',
        'exam_results'
    ];

    protected $casts = [
        'exam_results' => 'array',
    ];

    protected $appends = ['exam_results'];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicRecord')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
