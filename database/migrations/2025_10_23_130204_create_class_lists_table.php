<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('class_lists')) {
            $this->repairExistingTable();

            return;
        }

        Schema::create('class_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $this->addStudentReferenceColumn($table);
            $table->enum('type', ['provisional', 'verified', 'waiting', 'final', 'failed']);
            $table->json('attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_lists');
    }

    private function addStudentReferenceColumn(Blueprint $table): void
    {
        if (Schema::hasTable('student_applications')) {
            $table->foreignId('student_application_id')->unique()->constrained('student_applications');

            return;
        }

        $table->foreignId('student_program_id')->unique()->constrained('student_programs');
    }

    private function repairExistingTable(): void
    {
        if (
            Schema::hasColumn('class_lists', 'student_program_id')
            && ! Schema::hasColumn('class_lists', 'student_application_id')
            && Schema::hasTable('student_applications')
        ) {
            Schema::table('class_lists', function (Blueprint $table): void {
                $table->renameColumn('student_program_id', 'student_application_id');
            });
        }

        if (
            Schema::hasColumn('class_lists', 'student_application_id')
            && Schema::hasTable('student_applications')
            && ! $this->foreignKeyExists('class_lists', 'student_application_id')
        ) {
            Schema::table('class_lists', function (Blueprint $table): void {
                $table->foreign('student_application_id')
                    ->references('id')
                    ->on('student_applications');
            });
        }

        if (
            Schema::hasColumn('class_lists', 'student_program_id')
            && Schema::hasTable('student_programs')
            && ! $this->foreignKeyExists('class_lists', 'student_program_id')
        ) {
            Schema::table('class_lists', function (Blueprint $table): void {
                $table->foreign('student_program_id')
                    ->references('id')
                    ->on('student_programs');
            });
        }
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
};
