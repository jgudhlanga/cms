<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per enrolment per calendar period: the student's (or an admin's, or an automatic
        // rule's) statement of which programme phase they are studying in that period. Updated in
        // place; the activity log keeps the history.
        Schema::create('student_study_position_confirmations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'stu_study_pos_tenant_fk')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students', 'id', 'stu_study_pos_student_fk')->cascadeOnDelete();
            $table->foreignId('student_enrolment_id')
                ->constrained('student_enrolments', 'id', 'stu_study_pos_enrol_fk')
                ->cascadeOnDelete();
            $table->foreignId('academic_calendar_id')
                ->constrained('academic_calendars', 'id', 'stu_study_pos_period_fk')
                ->cascadeOnDelete();
            $table->foreignId('semester_id')
                ->nullable()
                ->constrained('semesters', 'id', 'stu_study_pos_slot_fk')
                ->nullOnDelete();
            $table->foreignId('programme_semester_id')
                ->nullable()
                ->constrained('programme_semesters', 'id', 'stu_study_pos_phase_fk')
                ->nullOnDelete();
            $table->foreignId('previous_programme_semester_id')
                ->nullable()
                ->constrained('programme_semesters', 'id', 'stu_study_pos_prev_phase_fk')
                ->nullOnDelete();
            $table->foreignId('student_semester_id')
                ->nullable()
                ->constrained('student_semesters', 'id', 'stu_study_pos_stu_sem_fk')
                ->nullOnDelete();
            $table->string('answer', 32);
            $table->string('source', 48);
            $table->string('sync_status', 32);
            $table->text('sync_note')->nullable();
            $table->text('reason')->nullable();
            $table->json('evidence')->nullable();
            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users', 'id', 'stu_study_pos_user_fk')
                ->nullOnDelete();
            $table->timestamp('confirmed_at');
            $table->timestamps();

            $table->unique(['student_enrolment_id', 'academic_calendar_id'], 'stu_study_pos_enrol_period_unq');
            $table->index(['student_id', 'academic_calendar_id'], 'stu_study_pos_student_period_idx');
            $table->index(['academic_calendar_id', 'answer', 'sync_status'], 'stu_study_pos_period_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_study_position_confirmations');
    }
};
