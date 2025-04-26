<?php

namespace Database\Seeders\Relationships;

use App\Enums\RelationshipEnum;
use App\Models\Relationships\Relationship;
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
