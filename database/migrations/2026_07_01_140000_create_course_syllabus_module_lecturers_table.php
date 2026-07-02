<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_syllabus_module_lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('course_syllabus_module_id')
                ->constrained('course_syllabus_modules', 'id', 'csm_lecturers_module_fk')
                ->cascadeOnDelete();
            $table->foreignId('staff_id')
                ->constrained('staff', 'id', 'csm_lecturers_staff_fk')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_syllabus_module_id', 'staff_id'], 'csm_lecturers_module_staff_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_syllabus_module_lecturers');
    }
};
