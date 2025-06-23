<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\PaymentFrequencyEnum;
use App\Models\Shared\PaymentFrequency;
use Illuminate\Database\Seeder;

class PaymentFrequencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        foreach (PaymentFrequencyEnum::cases() as $row) {
            $exist = PaymentFrequency::where('title', $row->value)->first();
            if (!$exist instanceof PaymentFrequency) {
                PaymentFrequency::create(['title' => $row->value]);
            }
        }
    }
}
