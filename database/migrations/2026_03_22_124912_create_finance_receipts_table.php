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
        Schema::create('finance_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_payment_id')->nullable()->constrained('bank_payments')->nullOnDelete();
            $table->string('receipt_number');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->date('payment_date');
            $table->timestamps();

            $table->unique(['tenant_id', 'receipt_number']);
            $table->index('student_id');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_receipts');
    }
};
