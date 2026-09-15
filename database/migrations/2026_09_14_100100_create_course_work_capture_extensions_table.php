<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_work_capture_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('academic_calendar_class_id')->constrained(indexName: 'cwce_class_fk')->cascadeOnDelete();
            $table->foreignId('course_syllabus_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('institution_department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_calendar_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->date('requested_until');
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('approved_until')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(
                ['academic_calendar_class_id', 'course_syllabus_module_id', 'assessment_type_id', 'status'],
                'cw_capture_extensions_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_work_capture_extensions');
    }
};
