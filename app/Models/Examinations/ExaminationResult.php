<?php

namespace App\Models\Examinations;

use App\Models\Students\Student;
use App\Traits\BelongsToTenant;
use App\Traits\Paginatable;
use Database\Factories\Examinations\ExaminationResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExaminationResult extends Model
{
    /** @use HasFactory<ExaminationResultFactory> */
    use BelongsToTenant, HasFactory, Paginatable;

    protected $fillable = [
        'tenant_id',
        'examination_import_id',
        'student_id',
        'discipline',
        'course_code',
        'candidate_number',
        'surname',
        'first_names',
        'subject_code',
        'subject',
        'grade',
        'session',
        'session_date',
        'course_comment',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    public function examinationImport(): BelongsTo
    {
        return $this->belongsTo(ExaminationImport::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
