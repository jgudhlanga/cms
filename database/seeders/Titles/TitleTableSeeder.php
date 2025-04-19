<?php

namespace Database\Seeders\Titles;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Titles\Title;

class TitleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data=['Mr', 'Mrs', 'Miss', 'Ms', 'Eng', 'Dr', 'Prof'];
        foreach ($data as $title)
        {
            Title::create(['name'=> $title]);
        }
    }
}
