<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('application_fees')) {
            $this->repairExistingTable();

            return;
        }

        Schema::create('application_fees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('intake_period_id')->constrained();
            $table->foreignId('level_id')->constrained();
            $table->foreignId('id_type_id')->nullable()->constrained('id_types');
            $table->string('id_number')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('status')->default('awaiting-payment');
            $this->addStudentReferenceColumn($table);
            $table->timestamps();

            $table->unique(['user_id', 'intake_period_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_fees');
    }

    private function addStudentReferenceColumn(Blueprint $table): void
    {
        if (Schema::hasTable('student_applications')) {
            $table->foreignId('student_application_id')->nullable()->constrained('student_applications')->nullOnDelete();

            return;
        }

        $table->foreignId('student_program_id')->nullable()->constrained('student_programs')->nullOnDelete();
    }

    private function repairExistingTable(): void
    {
        Schema::table('application_fees', function (Blueprint $table): void {
            if (! Schema::hasColumn('application_fees', 'id_type_id')) {
                $table->foreignId('id_type_id')->nullable()->after('level_id');
            }

            if (! Schema::hasColumn('application_fees', 'id_number')) {
                $table->string('id_number')->nullable()->after('id_type_id');
            }

            if (! Schema::hasColumn('application_fees', 'passport_number')) {
                $table->string('passport_number')->nullable()->after('id_number');
            }

            if (! Schema::hasColumn('application_fees', 'status')) {
                $table->string('status')->default('awaiting-payment')->after('passport_number');
            }
        });

        if (
            Schema::hasColumn('application_fees', 'student_program_id')
            && ! Schema::hasColumn('application_fees', 'student_application_id')
            && Schema::hasTable('student_applications')
        ) {
            Schema::table('application_fees', function (Blueprint $table): void {
                $table->renameColumn('student_program_id', 'student_application_id');
            });
        }

        $this->ensureForeignKey('application_fees', 'tenant_id', 'tenants');
        $this->ensureForeignKey('application_fees', 'user_id', 'users');
        $this->ensureForeignKey('application_fees', 'intake_period_id', 'intake_periods');
        $this->ensureForeignKey('application_fees', 'level_id', 'levels');

        if (Schema::hasColumn('application_fees', 'id_type_id')) {
            $this->ensureForeignKey('application_fees', 'id_type_id', 'id_types');
        }

        if (
            Schema::hasColumn('application_fees', 'student_application_id')
            && Schema::hasTable('student_applications')
        ) {
            $this->ensureForeignKey('application_fees', 'student_application_id', 'student_applications', nullOnDelete: true);
        }

        if (
            Schema::hasColumn('application_fees', 'student_program_id')
            && Schema::hasTable('student_programs')
        ) {
            $this->ensureForeignKey('application_fees', 'student_program_id', 'student_programs', nullOnDelete: true);
        }

        if (! $this->indexExists('application_fees', 'application_fees_user_id_intake_period_id_unique')) {
            Schema::table('application_fees', function (Blueprint $table): void {
                $table->unique(['user_id', 'intake_period_id']);
            });
        }

        if (! $this->indexExists('application_fees', 'application_fees_user_id_status_index')) {
            Schema::table('application_fees', function (Blueprint $table): void {
                $table->index(['user_id', 'status']);
            });
        }
    }

    private function ensureForeignKey(
        string $table,
        string $column,
        string $referencedTable,
        bool $nullOnDelete = false,
    ): void {
        if (! Schema::hasColumn($table, $column) || ! Schema::hasTable($referencedTable)) {
            return;
        }

        if ($this->foreignKeyExists($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $nullOnDelete): void {
            $foreign = $blueprint->foreign($column)
                ->references('id')
                ->on($referencedTable);

            if ($nullOnDelete) {
                $foreign->nullOnDelete();
            }
        });
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }
};
