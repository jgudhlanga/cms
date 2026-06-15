<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_syllabus_import_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('institution_department_id')->constrained('institution_departments');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('ingest_run_id')->nullable()->constrained('ingest_runs')->nullOnDelete();
            $table->string('original_filename');
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_succeeded')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['tenant_id', 'institution_department_id', 'created_at'],
                'cs_import_logs_tenant_dept_created_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_syllabus_import_logs');
    }
};
