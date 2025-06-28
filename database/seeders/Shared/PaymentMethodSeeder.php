<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\PaymentMethodEnum;
use App\Models\Shared\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{

    public function run(): void
    {
        foreach (PaymentMethodEnum::cases() as $row) {
            $exist = PaymentMethod::where('title', $row->value)->first();
            if (!$exist instanceof PaymentMethod) {
                PaymentMethod::create(['title' => $row->value]);
            }
        }
    }
}
