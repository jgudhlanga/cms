<?php

namespace Database\Seeders\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicYearOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AcademicYearOptionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            'Semester 1',
            'Semester 2',
            'Term 1',
            'Term 2',
            'Term 3',
            'Term 4',
            'ABMA 1',
            'ABMA 2',
            'ABMA 3',
            'ABMA 4',
        ];

        foreach ($rows as $name) {
            $slug = Str::slug($name);
            $record = AcademicYearOption::withTrashed()->where('slug', $slug)->first();

            if ($record instanceof AcademicYearOption) {
                $record->update([
                    'name' => $name,
                    'description' => $record->description,
                    'slug' => $slug,
                    'deleted_at' => null,
                ]);

                continue;
            }

            AcademicYearOption::query()->create([
                'name' => $name,
                'description' => null,
                'slug' => $slug,
            ]);
        }
    }
}
