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
    
        Schema::create('course_syllabuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('institution_department_id')->constrained('institution_departments');
            $table->foreignId('department_level_course_id')->constrained('department_level_courses');
            $table->string('title')->unique();
            $table->string('code')->unique();
            $table->string('implementation_year');
            $table->unsignedBigInteger('syllabus_document_id')->nullable();
            $table->enum('status', ['active', 'terminated'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_syllabuses');
    }
};
