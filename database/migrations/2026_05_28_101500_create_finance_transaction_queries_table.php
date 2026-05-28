<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_transaction_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_statement_id')->nullable()->constrained('zb_bank_statements')->nullOnDelete();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('declined_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_reference');
            $table->text('description')->nullable();
            $table->string('status')->default('submitted');
            $table->text('decline_reason')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'status']);
            $table->index('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transaction_queries');
    }
};
