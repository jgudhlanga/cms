<?php

namespace Database\Seeders;


// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\Countries\CountriesTableSeeder;
use Database\Seeders\Genders\GenderTableSeeder;
use Database\Seeders\Titles\TitleTableSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountriesTableSeeder::class,
            GenderTableSeeder::class,
            TitleTableSeeder::class,
        ]);
    }
}
