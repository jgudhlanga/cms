<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_billing_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->uuid('reference')->unique();
            $table->json('filters')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->string('status', 32);
            $table->foreignId('exported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('exported_at');
            $table->foreignId('billed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('billed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'exported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_billing_batches');
    }
};
