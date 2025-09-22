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
        foreach ($this->superUsers() as $superUser) {
            $user = User::where('email', $superUser['email'])->first();
            if (!$user) {
                $user = User::create($superUser);
                $user->assignRole(RoleEnum::SUPER_USER->name());
            }
        }
    }

    private function superUsers(): array
    {
        return  [
            [
                'first_name' => 'James',
                'middle_name' => 'Jimmy',
                'last_name' => 'Gudhlanga',
                'email' => 'jimmyneds@gmail.com',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                "phone_number" => "0788104809",
                'password' => 'P@5teF!5H',
                'status_id' => StatusEnum::ACTIVE->id(),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Tendai',
                'middle_name' => 'TK',
                'last_name' => 'Kumvekera',
                'email' => 'tkumvekera@hrepoly.ac.zw',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'password' => 'T3nd@!Kumv3k3r@',
                'status_id' => StatusEnum::ACTIVE->id(),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Nyasha',
                'middle_name' => 'Wilfred',
                'last_name' => 'Manhanga',
                'email' => 'nmanhanga@hrepoly.ac.zw',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'password' => 'Ny@5h@Wi!!',
                'status_id' => StatusEnum::ACTIVE->id(),
                'email_verified_at' => now(),
            ]
        ];
    }
}
