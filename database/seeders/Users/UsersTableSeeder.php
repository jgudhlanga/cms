<?php

namespace Database\Seeders\Users;

use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
     * Existing accounts keep their password and verification state. New accounts get a random
     * password; the owner signs in for the first time through "Forgot password".
     *
     * @param  array<string, mixed>  $attributes
     */
    private function upsertUser(array $attributes, string $roleName): void
    {
        $user = User::query()->where('email', $attributes['email'])->first();

        if ($user instanceof User) {
            $user->update(Arr::except($attributes, ['password', 'email_verified_at']));
        } else {
            $user = User::query()->create([
                ...$attributes,
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ]);
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
                'status_id' => StatusEnum::ACTIVE->id(),
            ],
            [
                'first_name' => 'Tendai',
                'middle_name' => 'TK',
                'last_name' => 'Kumvekera',
                'email' => 'tkumvekera@hrepoly.ac.zw',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'status_id' => StatusEnum::ACTIVE->id(),
            ],
            [
                'first_name' => 'Nyasha',
                'middle_name' => 'Wilfred',
                'last_name' => 'Manhanga',
                'email' => 'nmanhanga@hrepoly.ac.zw',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'status_id' => StatusEnum::ACTIVE->id(),
            ],
            [
                'first_name' => 'Peter',
                'middle_name' => null,
                'last_name' => 'Mudhluli',
                'email' => 'pmudhluli@hrepoly.ac.zw',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'status_id' => StatusEnum::ACTIVE->id(),
            ],
            [
                'first_name' => 'Shadreck',
                'middle_name' => null,
                'last_name' => 'Mahoseni',
                'email' => 'smahoseni@hrepoly.ac.zw',
                'tenant_id' => TenantEnum::HARARE_POLY->id(),
                'status_id' => StatusEnum::ACTIVE->id(),
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
                'status_id' => StatusEnum::ACTIVE->id(),
            ],
        ];
    }
}
