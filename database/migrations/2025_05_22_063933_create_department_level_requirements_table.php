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
        Schema::create('department_level_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('department_level_id')->index()->constrained();
            $table->boolean('is_o_level_required');
            $table->integer('required_subjects_count')->nullable();
            $table->integer('main_subjects_count')->nullable();
            $table->json('main_subject_ids')->nullable(); // ✅ JSON for arrays
            $table->integer('other_subjects_count')->nullable();
            $table->boolean('only_read_write_required');
            $table->boolean('is_previous_level_required');
            $table->unsignedBigInteger('previous_level_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_level_requirements');
    }
};
