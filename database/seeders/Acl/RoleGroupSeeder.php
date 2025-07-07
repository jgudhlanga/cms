<?php

namespace Database\Seeders\Acl;

use App\Enums\Acl\RoleGroupEnum;
use App\Models\Acl\RoleGroup;
use Illuminate\Database\Seeder;

class RoleGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (RoleGroupEnum::cases() as $row) {
            $exist = RoleGroup::where('name', $row->name())->first();
            if (!$exist instanceof RoleGroup) {
                RoleGroup::create([
                    'name' => $row->name(),
                    'description' => $row->description(),
                ]);
            }
        }
    }
}
