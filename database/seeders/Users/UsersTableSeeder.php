<?php

namespace Database\Seeders\Users;

use App\Enums\RoleEnum;
use App\Enums\TenantEnum;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    public function run(): void
    {
        $hararePoly = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        $sdu = User::create([
            'name' => 'Super User',
            'email' => 'su@penstejsystems.com',
            'tenant_id' => $hararePoly->id,
            'password' => 'Deve10per!23',
        ]);
        $sdu->assignRole(RoleEnum::SUPER_ADMINISTRATOR);

        $developer = User::create([
            'name' => 'Software Developer',
            'email' => 'developer@penstejsystems.com',
            'tenant_id' => $hararePoly->id,
            'password' => 'Deve10per!23',
        ]);
        $developer->assignRole(RoleEnum::SUPER_ADMINISTRATOR);
    }
}
