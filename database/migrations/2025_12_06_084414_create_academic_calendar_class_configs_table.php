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
        Schema::create('academic_calendar_class_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('academic_calendar_id')->constrained();
            $table->foreignId('institution_department_id')->constrained();
            $table->foreignId('department_course_id')->constrained();
            $table->foreignId('department_level_id')->constrained();
            $table->bigInteger('student_per_class')->default(0);
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_class_configs');
    }
};
