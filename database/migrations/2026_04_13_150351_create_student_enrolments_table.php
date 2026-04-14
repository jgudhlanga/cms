<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('institution_department_id')->constrained();
            $table->foreignId('department_level_id')->constrained();
            $table->foreignId('department_course_id')->constrained();
            $table->foreignId('academic_year_option_id')->constrained();
            $table->foreignId('academic_calendar_id')->constrained();
            $table->foreignId('student_enrolment_status_id')->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrolments');
    }
};
