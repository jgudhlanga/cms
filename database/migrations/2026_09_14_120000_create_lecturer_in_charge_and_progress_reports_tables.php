<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One Lecturer in Charge per class config (department course + level + mode of study for a year),
        // assigned by the HOD. Changes are kept in the activity log.
        Schema::create('class_config_lecturers_in_charge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('class_config_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Frozen coursework capture progress the Lecturer in Charge reports to the HOD.
        Schema::create('course_work_progress_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('class_config_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_calendar_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->json('snapshot');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('hod_comment')->nullable();
            $table->timestamps();

            $table->index(['institution_department_id', 'acknowledged_at'], 'cw_progress_reports_department_ack_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_work_progress_reports');
        Schema::dropIfExists('class_config_lecturers_in_charge');
    }
};
