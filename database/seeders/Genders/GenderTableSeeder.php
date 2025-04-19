<?php

namespace Database\Seeders\Genders;

use App\Models\Genders\Gender;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GenderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data=['Male', 'Female', 'Other'];
        foreach ($data as $gender)
        {
            Gender::create(['name'=> $gender]);
        }
    }
}
