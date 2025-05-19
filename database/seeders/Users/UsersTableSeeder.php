<?php

namespace Database\Seeders\Users;

use App\Enums\RoleEnum;
use App\Enums\TenantEnum;
use App\Models\Genders\Gender;
use App\Models\Tenants\Tenant;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    public function run(): void
    {
        $hararePoly = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        $titleIds = Title::pluck('id')->toArray();
        $genderIds = Gender::pluck('id')->toArray();
        $sdu = User::create([
            'first_name' => 'Super',
            'middle_name' => '',
            'last_name' => 'Administrator',
            'email' => 'su@penstejsystems.com',
            'tenant_id' => $hararePoly->id,
            'password' => 'Deve10per!23',
            'title_id' => fake()->randomElement($titleIds),
            'gender_id' => fake()->randomElement($genderIds),
        ]);
        $sdu->assignRole(RoleEnum::SUPER_ADMINISTRATOR);
    }
}
