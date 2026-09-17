<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Enums\Finance\StudentBillingStatusEnum;
use App\Models\Finance\StudentBillingBatch;
use App\Models\Finance\StudentBillingRecord;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentBillingRecord>
 */
class StudentBillingRecordFactory extends Factory
{
    protected $model = StudentBillingRecord::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->value('id'),
            'student_billing_batch_id' => StudentBillingBatch::factory(),
            'student_number' => fake()->unique()->numerify('26##########'),
            'status' => StudentBillingStatusEnum::EXPORTED,
            'exported_at' => now(),
            'billed_at' => null,
            'billed_by' => null,
        ];
    }
}
