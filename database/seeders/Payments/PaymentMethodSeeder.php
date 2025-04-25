<?php

namespace Database\Seeders\Payments;

use App\Models\Payments\PaymentMethod;
use Illuminate\Database\Seeder;
use App\Enums\PaymentMethodEnum;

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
