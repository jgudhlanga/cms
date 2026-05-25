<?php

namespace Database\Seeders\AcademicCalendars;

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Models\AcademicCalendars\ClassMetaDataType;
use Illuminate\Database\Seeder;

class ClassMetaDataTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ClassMetaDataTypeEnum::cases() as $row) {
            $exist = ClassMetaDataType::where('name', $row->value)->withTrashed()->first();
            if (! $exist instanceof ClassMetaDataType) {
                ClassMetaDataType::create([
                    'name' => $row->value,
                    'description' => $row->label(),
                ]);
            }
        }
    }
}
