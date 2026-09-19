<?php

declare(strict_types=1);

namespace Database\Factories\Institution;

use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfferLetterTemplate>
 */
class OfferLetterTemplateFactory extends Factory
{
    protected $model = OfferLetterTemplate::class;

    public function definition(): array
    {
        $tenantId = Tenant::query()->value('id') ?? Tenant::factory();

        $intakePeriodId = IntakePeriod::query()
            ->when(is_numeric($tenantId), fn ($query) => $query->where('tenant_id', $tenantId))
            ->value('id');

        if ($intakePeriodId === null) {
            $intakePeriodId = IntakePeriod::query()->create([
                'tenant_id' => $tenantId,
                'name' => fake()->unique()->words(3, true),
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ])->id;
        }

        return [
            'tenant_id' => $tenantId,
            'intake_period_id' => $intakePeriodId,
            'name' => fake()->unique()->words(3, true).' '.fake()->numerify('####'),
            'body' => '<p>{studentName} {tuition}</p>',
            'header_line_1' => 'Republic of Zimbabwe',
            'header_line_2' => 'Harare Polytechnic',
            'helper_description' => 'Applies to this intake when no more specific template matches.',
        ];
    }
}
