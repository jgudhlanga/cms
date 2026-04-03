<?php

use App\Models\AcademicCalendars\ClassMetaDataType;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;

test('class meta data type seeder creates expected rows', function () {
    $this->seed(ClassMetaDataTypeSeeder::class);

    expect(ClassMetaDataType::query()->count())->toBe(2)
        ->and(ClassMetaDataType::query()->where('name', 'lecturer')->exists())->toBeTrue()
        ->and(ClassMetaDataType::query()->where('name', 'time-table')->exists())->toBeTrue()
        ->and(ClassMetaDataType::query()->where('name', 'lecturer')->value('description'))->toBe('Lecturer')
        ->and(ClassMetaDataType::query()->where('name', 'time-table')->value('description'))->toBe('Time table');
});

test('class meta data type seeder is idempotent', function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
    $this->seed(ClassMetaDataTypeSeeder::class);

    expect(ClassMetaDataType::query()->count())->toBe(2);
});
