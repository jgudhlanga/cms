<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Enums\Finance\StudentBillingStatusEnum;
use App\Models\Finance\StudentBillingBatch;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StudentBillingBatch>
 */
class StudentBillingBatchFactory extends Factory
{
    protected $model = StudentBillingBatch::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->value('id'),
            'reference' => (string) Str::uuid(),
            'filters' => [],
            'row_count' => 0,
            'status' => StudentBillingStatusEnum::EXPORTED,
            'exported_by' => null,
            'exported_at' => now(),
            'billed_by' => null,
            'billed_at' => null,
            'note' => null,
        ];
    }
}
