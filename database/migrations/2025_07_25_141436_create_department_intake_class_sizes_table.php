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
        Schema::create('department_intake_class_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('intake_period_id')->constrained();
            $table->foreignId('institution_department_id')->constrained();
            $table->foreignId('department_course_id')->constrained();
            $table->foreignId('department_level_id')->constrained();
            $table->foreignId('mode_of_study_id')->constrained();
            $table->bigInteger('class_size')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_intake_class_sizes');
    }
};
