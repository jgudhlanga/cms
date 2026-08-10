<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_clearances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedSmallInteger('calendar_year');
            $table->foreignId('semester_id')->constrained('semesters');

            $table->boolean('accounts_cleared')->default(false);
            $table->foreignId('accounts_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accounts_cleared_at')->nullable();
            $table->text('accounts_notes')->nullable();

            $table->boolean('library_cleared')->default(false);
            $table->foreignId('library_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('library_cleared_at')->nullable();
            $table->text('library_notes')->nullable();

            $table->boolean('security_cleared')->default(false);
            $table->foreignId('security_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('security_cleared_at')->nullable();
            $table->text('security_notes')->nullable();

            $table->boolean('hostel_cleared')->default(false);
            $table->foreignId('hostel_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hostel_cleared_at')->nullable();
            $table->text('hostel_notes')->nullable();

            $table->boolean('department_cleared')->default(false);
            $table->foreignId('department_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('department_cleared_at')->nullable();
            $table->text('department_notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['student_id', 'calendar_year', 'semester_id'],
                'student_clearances_unique'
            );
            $table->index(['tenant_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_clearances');
    }
};
