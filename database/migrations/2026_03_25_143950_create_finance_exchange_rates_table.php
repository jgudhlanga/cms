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
        Schema::create('finance_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('currency_from');
            $table->string('currency_to');
            // Store the rate as a string to preserve exact decimal digits (including trailing zeros).
            $table->string('rate');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_exchange_rates');
    }
};
