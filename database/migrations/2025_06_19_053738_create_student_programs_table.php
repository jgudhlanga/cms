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
        Schema::create('student_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('institution_department_id')->constrained();
            $table->foreignId('department_level_id')->constrained();
            $table->foreignId('department_course_id')->constrained();
            $table->foreignId('department_application_step_id')->nullable();
            $table->foreignId('intake_period_id')->nullable();
            $table->foreignId('mode_of_study_id')->nullable();
            $table->unsignedBigInteger('program_status_id')->nullable();
            $table->boolean('required_level_completed')->nullable();
            $table->boolean('read_write_acknowledged')->nullable();
            $table->boolean('registration_fee_confirmed')->default(0)->nullable();
            $table->boolean('tuition_fee_confirmed')->default(0)->nullable();
            $table->boolean('offer_accepted')->default(0)->nullable();
            $table->string('application_tracking_number')->nullable();
            $table->unsignedBigInteger('offer_letter_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_applications');
    }
};
