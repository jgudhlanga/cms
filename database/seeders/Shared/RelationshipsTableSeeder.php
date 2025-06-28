<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\RelationshipEnum;
use App\Models\Shared\Relationship;
use Illuminate\Database\Seeder;

class RelationshipsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (RelationshipEnum::cases() as $row) {
            Relationship::create(['name' => $row->value]);
        }
    }
}
