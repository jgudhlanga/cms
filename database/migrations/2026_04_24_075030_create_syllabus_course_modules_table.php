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
        Schema::create('syllabus_course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('course_syllabus_id')->constrained('course_syllabuses');
            $table->string('title');
            $table->string('code')->unique();
            $table->unsignedInteger('duration_in_hours')->nullable();
            $table->unsignedInteger('nql_level')->nullable();
            $table->json('prerequisite_module_ids')->nullable();
            $table->boolean('shared')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syllabus_course_modules');
    }
};
