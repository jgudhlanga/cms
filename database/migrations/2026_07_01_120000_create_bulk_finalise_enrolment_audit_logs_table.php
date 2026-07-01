<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_finalise_enrolment_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->uuid('run_id');
            $table->string('event');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('student_application_id')
                ->nullable()
                ->constrained('student_applications')
                ->name('bf_enrol_audit_student_application_fk');
            $table->string('payment_eligibility')->nullable();
            $table->boolean('force_finalise')->default(false);
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['tenant_id', 'run_id', 'created_at'],
                'bf_enrol_audit_tenant_run_created_idx',
            );
            $table->index(
                ['student_application_id', 'created_at'],
                'bf_enrol_audit_application_created_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_finalise_enrolment_audit_logs');
    }
};
