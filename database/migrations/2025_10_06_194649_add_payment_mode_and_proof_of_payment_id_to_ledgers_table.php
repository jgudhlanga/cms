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
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('payment_mode', ['online', 'cash'])
                ->nullable()
                ->after('payment_option');
            $table->unsignedBigInteger('proof_of_payment_id')
                ->nullable()
                ->after('payment_mode');
            $table->string('payment_gateway')->nullable()->after('payment_mode');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropColumn(['payment_mode', 'proof_of_payment_id', 'payment_gateway']);
        });
    }
};
