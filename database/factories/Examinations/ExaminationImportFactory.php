<?php

namespace Database\Factories\Examinations;

use App\Enums\Examinations\ExaminationImportSourceEnum;
use App\Enums\Examinations\ExaminationImportStatusEnum;
use App\Models\Examinations\ExaminationImport;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExaminationImport>
 */
class ExaminationImportFactory extends Factory
{
    protected $model = ExaminationImport::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'source' => ExaminationImportSourceEnum::Upload,
            'status' => ExaminationImportStatusEnum::Pending,
            'original_filename' => 'exam-dump.xlsx',
            'stored_path' => 'examinations/uploads/exam-dump.xlsx',
            'rows_total' => 0,
            'rows_processed' => 0,
            'rows_upserted' => 0,
            'rows_failed' => 0,
            'error_message' => null,
            'started_by' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
