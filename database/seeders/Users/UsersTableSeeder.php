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
                "phone_number" => "0773095608",
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
                "phone_number" => "0715427571",
                'email' => 'nmanhanga@hrepoly.ac.zw',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'password' => 'Ny@5h@Wi!!',
                'status_id' => StatusEnum::ACTIVE->id(),
                'email_verified_at' => now(),
            ],
            [
            'first_name' => 'Peter',
            'middle_name' => null,
            'last_name' => 'Mudhluli',
                "phone_number" => "0773505891",
            'email' => 'pmudhluli@hrepoly.ac.zw',
            'tenant_id' => TenantEnum::HARARE_POLY->id(),
            'password' => '93ter@Mudh!',
            'status_id' => StatusEnum::ACTIVE->id(),
            'email_verified_at' => now(),
        ],
            [
                'first_name' => 'Shadreck',
                'middle_name' => null,
                'last_name' => 'Mahoseni',
                "phone_number" => "0774707111",
                'email' => 'smahoseni@hrepoly.ac.zw',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'password' => '5M@hosen!',
                'status_id' => StatusEnum::ACTIVE->id(),
                'email_verified_at' => now(),
            ]
        ];
    }
}
