<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('institution_department_id')->constrained();
            $table->foreignId('department_level_id')->constrained();
            $table->foreignId('department_course_id')->constrained();
            $table->foreignId('department_application_step_id')->nullable();
            $table->foreignId('intake_period_id')->nullable();
            $table->unsignedBigInteger('program_status_id')->nullable();
            $table->boolean('required_level_completed')->nullable();
            $table->boolean('read_write_acknowledged')->nullable();
            $table->string('application_tracking_number')->nullable();
            $table->unsignedBigInteger('application_fee_proof_of_payment_id')->nullable();
            $table->unsignedBigInteger('tution_fee_proof_of_payment_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_programs');
    }
};
