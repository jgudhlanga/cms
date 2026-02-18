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
        Schema::create('class_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_calendar_id')->constrained();
            $table->foreignId('institution_department_id')->constrained();
            $table->foreignId('department_course_id')->constrained();
            $table->foreignId('department_level_id')->constrained();
            $table->unsignedBigInteger('students_per_class')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_configs');
    }
};
