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
     * Index names are spelled out: generated names can pass MySQL's 64-character identifier limit.
     *
     * @var array<string, array<string, list<string>>>
     */
    private array $indexes = [
        'ledgers' => [
            'ledgers_application_type_status_idx' => ['student_application_id', 'type', 'payment_status'],
            'ledgers_intake_period_idx' => ['intake_period_id'],
            'ledgers_proof_of_payment_idx' => ['proof_of_payment_id'],
        ],
        'class_lists' => [
            'class_lists_type_idx' => ['type'],
        ],
        'student_applications' => [
            'student_applications_program_status_idx' => ['program_status_id'],
            'student_applications_offer_letter_idx' => ['offer_letter_id'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (! Schema::hasColumns($table, $columns) || Schema::hasIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($name, $columns): void {
                    $blueprint->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                    $blueprint->dropIndex($name);
                });
            }
        }
    }
};
