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
        Schema::create('zb_bank_statements', function (Blueprint $table) {
            $table->id();
            $table->string('tran_number_asc');
            $table->string('tran_number_desc');
            $table->string('transaction_id')->unique();
            $table->string('transaction_sr_id');
            $table->string('transaction_date');
            $table->text('narration')->nullable();
            $table->string('reference')->nullable();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->string('debit_credit_flag')->nullable();
            $table->string('amount_credit')->nullable();
            $table->string('amount_debit')->nullable();
            $table->string('cleared_running_balance')->nullable();
            $table->string('blocked_balance')->nullable();
            $table->string('debit_limit')->nullable();
            $table->string('credit_limit')->nullable();
            $table->string('iso_currency_code')->nullable();
            $table->string('account_description')->nullable();
            $table->string('ubfull_name')->nullable();
            $table->string('pipe_count')->nullable();
            $table->string('pipe1')->nullable();
            $table->string('pipe2')->nullable();
            $table->string('pipe3')->nullable();
            $table->string('pipe4')->nullable();
            $table->string('pipe5')->nullable();
            $table->string('pipe6')->nullable();
            $table->string('pipe7')->nullable();
            $table->string('pipe8')->nullable();
            $table->string('pipe9')->nullable();
            $table->string('pipe10')->nullable();
            $table->text('pipe1_details')->nullable();
            $table->text('pipe2_details')->nullable();
            $table->text('pipe3_details')->nullable();
            $table->text('pipe4_details')->nullable();
            $table->text('pipe5_details')->nullable();
            $table->text('pipe6_details')->nullable();
            $table->text('pipe7_details')->nullable();
            $table->text('pipe8_details')->nullable();
            $table->text('pipe9_details')->nullable();
            $table->text('pipe10_details')->nullable();
            $table->text('transaction_details')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zb_bank_statements');
    }
};
