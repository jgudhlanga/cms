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
        $tenant = Tenant::where('name', TenantEnum::PENSTEJ_SYSTEMS->value)->first();
        $sdu = User::create([
            'name' => 'Super User',
            'email' => 'su@penstejsystems.com',
            'tenant_id' => $tenant->id,
            'password' => 'Deve10per!23',
        ]);
        $sdu->assignRole(RoleEnum::SUPER_ADMINISTRATOR);
        $developer = User::create([
            'name' => 'Software Developer',
            'email' => 'developer@penstejsystems.com',
            'tenant_id' => $tenant->id,
            'password' => 'Deve10per!23',
        ]);
        $developer->assignRole(RoleEnum::SUPER_ADMINISTRATOR);
    }
}
