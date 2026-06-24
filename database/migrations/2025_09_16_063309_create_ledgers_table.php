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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->morphs('ledgerable');
            $table->foreignId('fee_type_id')->constrained();
            $table->foreignId('level_id')->nullable();
            $table->foreignId('student_application_id')->nullable();
            $table->string('payment_option')->nullable();
            $table->enum('type', ['receipt', 'invoice'])->default('invoice');
            $table->enum('payment_status', ['cancelled', 'failed', 'pending', 'paid'])->nullable();
            $table->float('amount');
            $table->float('client_fee')->nullable();
            $table->float('merchant_fee')->nullable();
            $table->string('currency')->nullable();
            $table->string('system_reference'); // Internal reference number
            $table->string('payment_reference')->nullable(); // External gateway reference
            $table->date('due_date')->nullable(); // For invoices
            $table->string('response_message')->nullable(); // For invoices
            $table->string('response_code')->nullable(); // For invoices
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
