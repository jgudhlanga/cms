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
            'first_name' => 'James',
            'middle_name' => 'Jimmy',
            'last_name' => 'Gudhlanga',
            'email' => 'jimmyneds@gmail.com',
            'tenant_id' => TenantEnum::HARARE_POLY->id(),
            "phone_number" => "0788104809",
            'password' => 'P@5teF!5H',
            'status_id' => StatusEnum::ACTIVE->id(),
            'email_verified_at' => now(),
        ]);
        $sdu->assignRole(RoleEnum::SUPER_USER->name());
    }
}
