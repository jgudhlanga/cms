<?php

namespace Database\Seeders\Users;

use App\Enums\Shared\RoleEnum;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Shared\Status;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    public function run(): void
    {
        $hararePoly = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        $status = Status::where('title', StatusEnum::ACTIVE->value)->first();
        $sdu = User::create([
            'first_name' => 'Super',
            'middle_name' => '',
            'last_name' => 'Administrator',
            'email' => 'penstejdevelopers@gmail.com',
            'tenant_id' => $hararePoly->id,
            'password' => 'Developer123!',
            'status_id' => $status->id,
            'email_verified_at' => now(),
        ]);
        $sdu->assignRole(RoleEnum::SUPER_ADMINISTRATOR);
    }
}
