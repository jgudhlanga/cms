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
            $table->unsignedBigInteger('proof_of_payment_id')
                ->nullable()
                ->after('payment_option');
            $table->string('payment_gateway')->nullable()->after('proof_of_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropColumn(['proof_of_payment_id', 'payment_gateway']);
        });
    }
};
