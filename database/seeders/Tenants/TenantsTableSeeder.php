<?php

namespace Database\Seeders\Tenants;

use App\Enums\TenantEnum;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Seeder;

class TenantsTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TenantEnum::cases() as $row) {
            Tenant::create(['name' => $row->value]);
        }
    }
}
