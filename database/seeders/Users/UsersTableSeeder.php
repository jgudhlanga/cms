<?php

namespace Database\Seeders\Users;

use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Users\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->superUsers() as $superUser) {
            $this->upsertUser($superUser, RoleEnum::SUPER_USER->name());
        }

        foreach ($this->supportUsers() as $supportUser) {
            $this->upsertUser($supportUser, RoleEnum::IT_SUPPORT_TECHNICIAN->name());
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertUser(array $attributes, string $roleName): void
    {
        $email = $attributes['email'];

        $user = User::query()->where('email', $email)->first();

        if ($user instanceof User) {
            $user->update($attributes);
        } else {
            $user = User::query()->create($attributes);
        }

        $user->syncRoles([$roleName]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function superUsers(): array
    {
        return [
            [
                'first_name' => 'James',
                'middle_name' => 'Jimmy',
                'last_name' => 'Gudhlanga',
                'email' => 'jimmyneds@gmail.com',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'phone_number' => '0788104809',
                'password' => 'P@5teF!5H',
                'status_id' => StatusEnum::ACTIVE->id(),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Tendai',
                'middle_name' => 'TK',
                'last_name' => 'Kumvekera',
                'phone_number' => '0773095608',
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
                'phone_number' => '0715427571',
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
                'phone_number' => '0773505891',
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
                'phone_number' => '0774707111',
                'email' => 'smahoseni@hrepoly.ac.zw',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'password' => '5M@hosen!',
                'status_id' => StatusEnum::ACTIVE->id(),
                'email_verified_at' => now(),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function supportUsers(): array
    {
        return [
            [
                'first_name' => 'Support',
                'middle_name' => null,
                'last_name' => 'Technician',
                'email' => 'support@hrepoly.ac.zw',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'phone_number' => null,
                'password' => 'S@pp0rt!T3ch',
                'status_id' => StatusEnum::ACTIVE->id(),
                'email_verified_at' => now(),
            ],
        ];
    }
}
