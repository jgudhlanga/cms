<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examination_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('examination_import_id')->nullable()->constrained('examination_imports')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('discipline')->nullable();
            $table->string('course_code')->nullable();
            $table->string('candidate_number');
            $table->string('surname')->nullable();
            $table->string('first_names')->nullable();
            $table->string('subject_code');
            $table->string('subject')->nullable();
            $table->string('grade')->nullable();
            $table->string('session');
            $table->date('session_date')->nullable();
            $table->string('course_comment')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'candidate_number', 'subject_code', 'session'],
                'examination_results_unique'
            );
            $table->index(['tenant_id', 'candidate_number']);
            $table->index('session_date');
            $table->index('surname');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_results');
    }
};
