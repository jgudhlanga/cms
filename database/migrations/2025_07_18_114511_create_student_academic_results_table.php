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
        Schema::create('student_academic_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('academic_level_id')->nullable()->constrained();
            $table->foreignId('subject_id')->nullable()->constrained();
            $table->string('exam_year')->nullable();
            $table->enum('exam_sitting', ['june' , 'november' , 'other'])->nullable();
            $table->foreignId('grade_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_academic_results');
    }
};
