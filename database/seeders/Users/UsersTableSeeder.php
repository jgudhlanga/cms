<?php

namespace Database\Seeders\Users;

use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Enums\TenantEnum;
use App\Enums\TitleEnum;
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
        $title = Title::where('name', TitleEnum::MR->value)->first();
        $gender = Gender::where('title', GenderEnum::MALE->value)->first();
        $sdu = User::create([
            'first_name' => 'Super',
            'middle_name' => '',
            'last_name' => 'Administrator',
            'email' => 'penstejdevelopers@gmail.com',
            'tenant_id' => $hararePoly->id,
            'password' => 'Developer123!',
            'title_id' => $title->id,
            'gender_id' => $gender->id,
            'email_verified_at' => now(),
        ]);
        $sdu->assignRole(RoleEnum::SUPER_ADMINISTRATOR);
    }
}
