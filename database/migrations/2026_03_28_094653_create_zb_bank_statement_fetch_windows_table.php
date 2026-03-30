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
        Schema::create('zb_bank_statement_fetch_windows', function (Blueprint $table) {
            $table->id();
            $table->string('account_type');
            $table->date('window_start');
            $table->date('window_end');
            $table->string('status');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(
                ['account_type', 'window_start', 'window_end'],
                'zb_bsfw_account_window_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zb_bank_statement_fetch_windows');
    }
};
