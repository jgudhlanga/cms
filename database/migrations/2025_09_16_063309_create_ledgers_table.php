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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->morphs('ledgerable');
            $table->foreignId('fee_type_id')->constrained();
            $table->foreignId('payment_method_id')->nullable();
            $table->enum('type', ['receipt', 'invoice'])->default('receipt');
            $table->enum('payment_status', ['success', 'cancelled', 'failed', 'pending'])->nullable();
            $table->float('amount');
            $table->string('system_reference')->unique(); // Internal reference number
            $table->string('payment_reference')->nullable(); // External gateway reference
            $table->date('due_date')->nullable(); // For invoices
            $table->timestamp('payment_date')->nullable(); // When payment was actually made
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
