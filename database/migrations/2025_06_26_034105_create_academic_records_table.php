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
        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('student_id')->constrained();
            $table->string('school');
            $table->string('place');
            $table->integer('from_level')->nullable();
            $table->integer('to_level')->nullable();
            $table->string('from_year')->nullable();
            $table->string('to_year')->nullable();
            $table->string('student_unique_number')->nullable();
            $table->string('exam_board')->nullable();
            $table->string('exam_month')->nullable();
            $table->string('exam_year')->nullable();
            $table->string('exam_center')->nullable();
            $table->json('exam_results')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_records');
    }
};
