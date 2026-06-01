<?php

namespace App\Models\AcademicCalendars;

use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelIngest\Models\IngestRun;

class CourseWorkImportLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'class_config_id',
        'course_syllabus_module_id',
        'user_id',
        'ingest_run_id',
        'original_filename',
        'rows_total',
        'rows_succeeded',
        'rows_failed',
        'rows_skipped',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function classConfig(): BelongsTo
    {
        return $this->belongsTo(ClassConfig::class);
    }

    public function courseSyllabusModule(): BelongsTo
    {
        return $this->belongsTo(CourseSyllabusModule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingestRun(): BelongsTo
    {
        return $this->belongsTo(IngestRun::class);
    }
}
