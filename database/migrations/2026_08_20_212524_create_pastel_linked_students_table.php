<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pastel_linked_students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('student_number')->nullable();
            $table->foreignId('intake_period_id')->nullable()->constrained('intake_periods')->nullOnDelete();
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('linked_at');
            $table->timestamps();

            $table->unique('student_id');
            $table->unique('student_number');
            $table->index(['tenant_id', 'linked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pastel_linked_students');
    }
};
