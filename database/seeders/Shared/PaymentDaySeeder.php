<?php

namespace Database\Seeders\Shared;

use App\Models\Shared\PaymentDay;
use Illuminate\Database\Seeder;

class PaymentDaySeeder extends Seeder
{

    public function run(): void
    {
        for ($day = 1; $day <= 31; $day++) {
            $exist = PaymentDay::where('title', $day)->first();
            if (!$exist instanceof PaymentDay) {
                PaymentDay::create(['title' => $day]);
            }
        }
    }
}
