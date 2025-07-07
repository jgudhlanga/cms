<?php

namespace Database\Seeders\Users;

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Users\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    public function run(): void
    {
        $sdu = User::create([
            'first_name' => 'Super',
            'middle_name' => '',
            'last_name' => 'Administrator',
            'email' => 'penstejdevelopers@gmail.com',
            'tenant_id' => TenantEnum::HARARE_POLY->id(),
            "phone_number" => "+27788104809",
            'password' => 'Developer123!',
            'status_id' => StatusEnum::ACTIVE->id(),
            'email_verified_at' => now(),
        ]);
        $sdu->assignRole(RoleEnum::SUPER_ADMINISTRATOR->name());
    }
}
