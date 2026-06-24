<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_fees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('intake_period_id')->constrained();
            $table->foreignId('level_id')->constrained();
            $table->foreignId('id_type_id')->nullable()->constrained('id_types');
            $table->string('id_number')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('status')->default('awaiting-payment');

            if (Schema::hasTable('student_applications')) {
                $table->foreignId('student_application_id')->nullable()->constrained('student_applications')->nullOnDelete();
            } else {
                $table->foreignId('student_program_id')->nullable()->constrained('student_programs')->nullOnDelete();
            }

            $table->timestamps();

            $table->unique(['user_id', 'intake_period_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_fees');
    }
};
