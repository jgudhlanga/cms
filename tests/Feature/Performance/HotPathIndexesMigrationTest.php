<?php

use Illuminate\Support\Facades\Schema;

/*
 * Tests run on SQLite, which accepts identifiers of any length. MySQL rejects index names over
 * 64 characters, so the limit is checked here explicitly.
 */

test('hot path index names fit the MySQL identifier limit', function () {
    $migration = require database_path('migrations/2026_09_15_130000_add_hot_path_indexes.php');
    $indexes = (new ReflectionProperty($migration, 'indexes'))->getValue($migration);

    foreach ($indexes as $table => $tableIndexes) {
        foreach (array_keys($tableIndexes) as $name) {
            expect(strlen($name))->toBeLessThanOrEqual(64, "{$table}: {$name}");
        }
    }
});

test('hot path indexes exist after migrating', function () {
    expect(Schema::hasIndex('ledgers', 'ledgers_application_type_status_idx'))->toBeTrue()
        ->and(Schema::hasIndex('class_lists', 'class_lists_type_idx'))->toBeTrue()
        ->and(Schema::hasIndex('student_applications', 'student_applications_offer_letter_idx'))->toBeTrue();
});
