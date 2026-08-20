<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Finance\PastelLinkedStudent;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PastelLinkedStudent>
 */
class PastelLinkedStudentFactory extends Factory
{
    protected $model = PastelLinkedStudent::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->value('id'),
            'student_number' => fake()->unique()->numerify('26##########'),
            'intake_period_id' => null,
            'linked_by' => null,
            'linked_at' => now(),
        ];
    }
}
