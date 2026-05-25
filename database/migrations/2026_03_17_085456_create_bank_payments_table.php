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
        Schema::create('bank_payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('bank');
            $table->float('amount');
            $table->timestamp('transaction_created_date');
            $table->string('narrative')->nullable();
            $table->string('nr1')->nullable();
            $table->string('nr2')->nullable();
            $table->string('nr3')->nullable();
            $table->string('nr4')->nullable();
            $table->string('picked')->nullable();
            $table->string('reference')->nullable();
            $table->string('source')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->string('tcd')->nullable();
            $table->timestamp('transaction_date')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_payments');
    }
};
