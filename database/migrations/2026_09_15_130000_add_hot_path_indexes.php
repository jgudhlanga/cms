<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Indexes for columns that fee, class list and application lookups filter on but were created without
 * one. Each index is skipped when a column is missing or an index on the same columns already exists.
 */
return new class extends Migration
{
    /**
     * @var array<string, list<list<string>>>
     */
    private array $indexes = [
        'ledgers' => [
            ['student_application_id', 'type', 'payment_status'],
            ['intake_period_id'],
            ['proof_of_payment_id'],
        ],
        'class_lists' => [
            ['type'],
        ],
        'student_applications' => [
            ['program_status_id'],
            ['offer_letter_id'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $columns) {
                if (! Schema::hasColumns($table, $columns) || Schema::hasIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                    $blueprint->index($columns, $this->indexName($table, $columns));
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $columns) {
                $name = $this->indexName($table, $columns);

                if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                    $blueprint->dropIndex($name);
                });
            }
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function indexName(string $table, array $columns): string
    {
        return $table.'_'.implode('_', $columns).'_hot_path_index';
    }
};
