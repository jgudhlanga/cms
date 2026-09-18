<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_billing_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_enrolment_id')
                ->constrained('student_enrolments', 'id', 'stu_billing_rec_enrol_fk')
                ->cascadeOnDelete();
            $table->foreignId('academic_calendar_id')
                ->constrained('academic_calendars', 'id', 'stu_billing_rec_period_fk')
                ->cascadeOnDelete();
            $table->foreignId('programme_semester_id')
                ->constrained('programme_semesters', 'id', 'stu_billing_rec_phase_fk')
                ->cascadeOnDelete();
            $table->foreignId('semester_id')
                ->nullable()
                ->constrained('semesters', 'id', 'stu_billing_rec_slot_fk')
                ->nullOnDelete();
            $table->foreignId('student_study_position_confirmation_id')
                ->constrained('student_study_position_confirmations', 'id', 'stu_billing_rec_conf_fk')
                ->cascadeOnDelete();
            $table->foreignId('student_billing_batch_id')
                ->constrained('student_billing_batches', 'id', 'stu_billing_rec_batch_fk')
                ->cascadeOnDelete();
            $table->string('student_number')->nullable();
            $table->string('status', 32);
            $table->timestamp('exported_at');
            $table->timestamp('billed_at')->nullable();
            $table->foreignId('billed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['student_enrolment_id', 'academic_calendar_id', 'programme_semester_id'],
                'stu_billing_rec_enrol_period_phase_unq',
            );
            $table->index(['tenant_id', 'status', 'billed_at'], 'stu_billing_rec_status_billed_idx');
            $table->index(['student_id', 'academic_calendar_id'], 'stu_billing_rec_student_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_billing_records');
    }
};
