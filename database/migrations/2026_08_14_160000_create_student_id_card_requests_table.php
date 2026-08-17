<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_id_card_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('supersedes_request_id')->nullable()->constrained('student_id_card_requests')->nullOnDelete();
            $table->foreignId('fee_ledger_id')->nullable()->constrained('ledgers')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_id_card_requests');
    }
};
