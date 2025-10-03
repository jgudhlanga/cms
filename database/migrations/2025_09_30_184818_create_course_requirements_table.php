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
        Schema::create('course_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('department_level_id')->constrained();
            $table->foreignId('department_course_id')->constrained();
            $table->boolean('is_o_level_required');
            $table->integer('required_subjects_count')->nullable();
            $table->integer('main_subjects_count')->nullable();
            $table->json('main_subject_ids')->nullable(); // ✅ JSON for arrays
            $table->integer('other_subjects_count')->nullable();
            $table->boolean('only_read_write_required');
            $table->unsignedBigInteger('required_level_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_requirements');
    }
};
