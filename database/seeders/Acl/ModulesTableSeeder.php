<?php

namespace Database\Seeders\Acl;

use App\Enums\Shared\ModuleEnum;
use App\Models\Acl\Module;
use Illuminate\Database\Seeder;

class ModulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ModuleEnum::cases() as $row) {
            $exist = Module::where('title', $row->value)->first();
            if (! $exist instanceof Module) {
                Module::create(['title' => $row->value]);
            }
        }
    }
}
