<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\DocumentTypeEnum;
use App\Models\Shared\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DocumentTypeEnum::cases() as $row) {
            DocumentType::create([
                'name' => $row->name(),
                'description' => $row->description(),
            ]);
        }
    }
}
