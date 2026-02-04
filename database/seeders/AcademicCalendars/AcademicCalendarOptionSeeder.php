<?php

namespace Database\Seeders\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarOptionEnum;
use App\Models\AcademicCalendars\AcademicCalendarOption;
use Illuminate\Database\Seeder;

class AcademicCalendarOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (AcademicCalendarOptionEnum::cases() as $row) {
            $exist = AcademicCalendarOption::where('name', $row->value)->withTrashed()->first();
            if (!$exist instanceof AcademicCalendarOption) {
                AcademicCalendarOption::create(['name' => $row->value]);
            }
        }
    }
}
