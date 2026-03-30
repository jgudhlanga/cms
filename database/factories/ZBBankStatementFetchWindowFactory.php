<?php

namespace Database\Factories;

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use App\Models\Integrations\Banks\ZBBankStatementFetchWindow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ZBBankStatementFetchWindow>
 */
class ZBBankStatementFetchWindowFactory extends Factory
{
    protected $model = ZBBankStatementFetchWindow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->date('Y-m-d');

        return [
            'account_type' => 'usd',
            'window_start' => $start,
            'window_end' => $start,
            'status' => ZBBankStatementFetchWindowStatus::Pending,
            'attempt_count' => 0,
        ];
    }
}
